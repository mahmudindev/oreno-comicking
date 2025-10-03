import type { JWTPayload, JWTVerifyGetKey } from 'jose';
import { createRemoteJWKSet, jwtVerify } from 'jose';
import { User } from './user';
import { AuthError, AuthErrorType } from '$lib/exception';

export interface AuthProvider {
	headers?: Headers;
}

export class Auth {
	static #auth?: Auth;

	#oauth: OAuth;

	private constructor(oauth: OAuth) {
		this.#oauth = oauth;
	}

	public static async getInstance(
		initOAuthAudience?: string,
		initOAuthIssuer?: string,
		initOAuthPermissionID?: string
	): Promise<Auth> {
		if (!this.#auth) {
			const oauth = new OAuth(initOAuthAudience, initOAuthIssuer, initOAuthPermissionID);
			await oauth.initialize();

			this.#auth = new Auth(oauth);
		}

		return this.#auth;
	}

	public async createUser(authProvider: AuthProvider): Promise<User | null> {
		let accessTokenRaw = '';

		if (authProvider.headers) {
			const authorization = authProvider.headers.get('Authorization');

			if (authorization?.startsWith('Bearer')) {
				accessTokenRaw = authorization.slice(7);
			}
		}

		if (accessTokenRaw) {
			const accessToken = await this.#oauth.parseAccessToken(accessTokenRaw);

			const user = new User(accessToken.getSubject() ?? '');

			const authInfo = user.getAuthInfo();
			authInfo.setAccessToken(accessToken);

			return user;
		}

		return null;
	}
}

export class AuthInfo {
	#accessToken?: AccessToken;

	constructor() {}

	public hasPermission(...permissions: string[][]): boolean {
		const accessToken = this.#accessToken;
		if (accessToken) {
			if (accessToken.hasPermission('SUPERADMIN')) {
				return true;
			}

			const oauth = accessToken.getOAuth();

			let permissionPrefix = '';

			const permissionID = oauth.getPermissionID();
			if (permissionID) {
				permissionPrefix = permissionID + oauth.getPermissionSeparator();
			}

			if (accessToken.hasPermission(permissionPrefix + 'SUPERADMIN')) {
				return true;
			}

			for (const permissionRaw of permissions) {
				const permission = permissionRaw.join(oauth.getPermissionSeparator());

				if (accessToken.hasPermission(permissionPrefix + permission)) {
					return true;
				}

				if (accessToken.hasPermission(permissionPrefix + 'ADMIN')) {
					if (!accessToken.hasPermission('!' + permissionPrefix + permission)) {
						return true;
					}
				}
			}
		}

		return false;
	}

	public getAccessToken(): AccessToken | undefined {
		return this.#accessToken;
	}

	public setAccessToken(accessToken: AccessToken): void {
		this.#accessToken = accessToken;
	}
}

export class OAuth {
	#audience?: string;
	#issuer?: string;
	#key?: JWTVerifyGetKey;
	#permissionID?: string;
	#permissionSeparator: string;

	constructor(
		audience?: string,
		issuer?: string,
		permissionID?: string,
		permissionSeparator: string = ':'
	) {
		this.#audience = audience;
		this.#issuer = issuer;
		this.#permissionID = permissionID;
		this.#permissionSeparator = permissionSeparator;
	}

	public async initialize(): Promise<void> {
		if (!this.#issuer) {
			throw new AuthError('oauth configuration need issuer to initialize');
		}

		let metadata = {
			issuer: '',
			jwks_uri: ''
		};

		const oauthDiscovery = this.#issuer + '.well-known/oauth-authorization-server';
		await fetch(oauthDiscovery).then(async (response) => {
			if (!response.ok) {
				return;
			}

			metadata = await response.json();
		});

		if (!metadata.issuer) {
			const oidcDiscovery = this.#issuer + '.well-known/openid-configuration';
			await fetch(oidcDiscovery).then(async (response) => {
				if (!response.ok) {
					return;
				}

				metadata = await response.json();
			});
		}

		if (metadata.issuer && metadata.issuer != this.#issuer) {
			throw new AuthError('oauth issuer does not match with discovery server');
		}

		if (!metadata.jwks_uri) {
			metadata.jwks_uri = this.#issuer + '.well-known/jwks.json';
		}

		this.#key = createRemoteJWKSet(new URL(metadata.jwks_uri));
	}

	public getAudience(): string | undefined {
		return this.#audience;
	}

	public getIssuer(): string | undefined {
		return this.#issuer;
	}

	public getKey(): JWTVerifyGetKey | undefined {
		return this.#key;
	}

	public getPermissionID(): string | undefined {
		return this.#permissionID;
	}

	public getPermissionSeparator(): string {
		return this.#permissionSeparator;
	}

	public async parseAccessToken(token: string) {
		return await AccessToken.parse(this, token);
	}
}

class AccessToken {
	#oauth: OAuth;
	#raw: string;
	#payload: JWTPayload;

	private constructor(oauth: OAuth, raw: string, payload: JWTPayload) {
		this.#oauth = oauth;
		this.#raw = raw;
		this.#payload = payload;
	}

	public static async parse(oauth: OAuth, token: string): Promise<AccessToken> {
		if (!token) {
			throw new AuthError('invalid access token', AuthErrorType.Unauthorized);
		}

		const oauthKey = oauth.getKey();

		if (!oauthKey) {
			throw new AuthError('oauth key is not initialized');
		}

		const result = await jwtVerify(token, oauthKey, {
			audience: oauth.getAudience(),
			issuer: oauth.getIssuer()
		});

		return new AccessToken(oauth, token, result.payload);
	}

	public getOAuth(): OAuth {
		return this.#oauth;
	}

	public getRaw(): string {
		return this.#raw;
	}

	public getSubject(): string | undefined {
		return this.#payload.sub;
	}

	public getExpiration(): number | undefined {
		return this.#payload.exp;
	}

	public getClaim(name: string): unknown {
		return this.#payload[name];
	}

	public hasScope(scope: string): boolean {
		return String(this.#payload['scope']).split(' ').includes(scope);
	}

	public hasPermission(permission: string): boolean {
		const permissions = this.#payload['permissions'];

		switch (typeof permissions) {
			case 'object':
				if (Array.isArray(permissions)) {
					//
					// Auth0 RBAC
					//

					return permissions
						.map((v) => {
							if (v instanceof String) {
								return v.toUpperCase();
							}

							return v;
						})
						.includes(permission);
				}
				break;
			case 'string':
				return permissions
					.split(' ')
					.map((v) => v.toUpperCase())
					.includes(permission);
		}

		return false;
	}
}
