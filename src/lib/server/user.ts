import { AuthInfo } from './auth';

export class User {
	#username: string;

	#authInfo: AuthInfo = new AuthInfo();

	constructor(username: string) {
		this.#username = username;
	}

	public static create(username: string): User {
		return new User(username);
	}

	public getUsername(): string {
		return this.#username;
	}

	public hasPermission(...permissions: string[][]): boolean {
		const authInfo = this.getAuthInfo();
		if (authInfo.hasPermission(...permissions)) {
			return true;
		}

		return false;
	}

	public getAuthInfo(): AuthInfo {
		return this.#authInfo;
	}
}
