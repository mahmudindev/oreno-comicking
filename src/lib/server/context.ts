import type { DB } from './database';
import type { AuthProvider } from './auth';
import { Database } from './database';
import { Auth } from './auth';
import { User } from './user';
import { env } from '$env/dynamic/private';

export async function getDatabase(): Promise<DB> {
	return await Database.getInstance(env.DATABASE_URL, env.DATABASE_SCHEMA);
}

export async function getUser(authProvider: AuthProvider): Promise<User | null> {
	const auth = await Auth.getInstance(
		env.OAUTH_AUDIENCE,
		env.OAUTH_ISSUER,
		env.OAUTH_PERMISSION_ID
	);

	return await auth.createUser(authProvider);
}
