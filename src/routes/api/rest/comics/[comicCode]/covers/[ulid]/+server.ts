import type { RequestHandler } from './$types';
import type { SetComicCover } from '$lib/model';
import { json } from '@sveltejs/kit';
import { getDatabase, getUser } from '$lib/server/context';
import {
	deleteComicCoverByKey,
	getComicCoverByKey,
	updateComicCoverByKey
} from '$lib/server/service';
import { Error415JSON, Error500JSON } from '$lib/api';
import { APIError, DatabaseError, GenericError, NotFoundError } from '$lib/exception';
import { stringRemoveSuffix } from '$lib/helper';

export const GET: RequestHandler = async ({ params, request }) => {
	try {
		const database = await getDatabase();
		const user = await getUser({ headers: request.headers });

		const result = await getComicCoverByKey({ user, database }, params.comicCode, params.ulid);

		return json(result, {
			headers: {
				'X-Content-Type-Options': 'nosniff'
			}
		});
	} catch (e) {
		let result = Error500JSON;
		let status = 500;

		if (e instanceof DatabaseError || e instanceof APIError) {
			result = { message: e.message };
		} else if (e instanceof NotFoundError) {
			result = { message: e.message };
			status = 404;
		} else if (e instanceof GenericError) {
			result = { message: e.message };
			status = 400;
		}

		if (e && status == 500) console.log(e);

		return json(result, { status });
	}
};

export const PATCH: RequestHandler = async ({ params, request }) => {
	let data: SetComicCover = {};

	switch (request.headers.get('Content-Type')) {
		case 'application/json':
			data = await request.json();
			break;
		case 'application/x-www-form-urlencoded': {
			const f = await request.formData();
			data.linkWebsiteHost = (f.get('link_website_host') as string | null) ?? undefined;
			data.linkRelativeReference = (f.get('link_relative_reference') as string | null) ?? undefined;
			data.hint = (f.get('content') as string | null) ?? undefined;
			break;
		}
		default:
			return json(Error415JSON, { status: 415 });
	}

	try {
		const database = await getDatabase();
		const user = await getUser({ headers: request.headers });

		const result = await updateComicCoverByKey(
			{ user, database },
			params.comicCode,
			params.ulid,
			data
		);

		if (!result) return new Response(undefined, { status: 204 });

		return json(result, {
			headers: {
				Location: stringRemoveSuffix(new URL(request.url).pathname, params.ulid) + result.ulid,
				'X-Content-Type-Options': 'nosniff'
			}
		});
	} catch (e) {
		let result = Error500JSON;
		let status = 500;

		if (e instanceof DatabaseError || e instanceof APIError) {
			result = { message: e.message };
		} else if (e instanceof NotFoundError) {
			result = { message: e.message };
			status = 404;
		} else if (e instanceof GenericError) {
			result = { message: e.message };
			status = 400;
		}

		if (e && status == 500) console.log(e);

		return json(result, { status });
	}
};

export const DELETE: RequestHandler = async ({ params, request }) => {
	try {
		const database = await getDatabase();
		const user = await getUser({ headers: request.headers });

		await deleteComicCoverByKey({ user, database }, params.comicCode, params.ulid);

		return new Response(undefined, { status: 204 });
	} catch (e) {
		let result = Error500JSON;
		let status = 500;

		if (e instanceof DatabaseError || e instanceof APIError) {
			result = { message: e.message };
		} else if (e instanceof NotFoundError) {
			result = { message: e.message };
			status = 404;
		} else if (e instanceof GenericError) {
			result = { message: e.message };
			status = 400;
		}

		if (e && status == 500) console.log(e);

		return json(result, { status });
	}
};
