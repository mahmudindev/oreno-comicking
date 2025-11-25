import type { RequestHandler } from './$types';
import type { SetComicAuthor } from '$lib/model';
import { json } from '@sveltejs/kit';
import { getDatabase, getUser } from '$lib/server/context';
import {
	deleteComicAuthorByKey,
	getComicAuthorByKey,
	updateComicAuthorByKey
} from '$lib/server/service';
import { Error415JSON, Error500JSON } from '$lib/api';
import { APIError, DatabaseError, GenericError, NotFoundError } from '$lib/exception';
import { stringRemoveSuffix } from '$lib/helper';

export const GET: RequestHandler = async ({ params, request }) => {
	try {
		const database = await getDatabase();
		const user = await getUser({ headers: request.headers });

		const result = await getComicAuthorByKey(
			{ user, database },
			params.comicCode,
			params.positionCode,
			params.personCode
		);

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
	let data: SetComicAuthor = {};

	switch (request.headers.get('Content-Type')) {
		case 'application/json':
			data = await request.json();
			break;
		case 'application/x-www-form-urlencoded': {
			const f = await request.formData();
			data.positionCode = (f.get('position_code') as string | null) ?? undefined;
			data.personCode = (f.get('person_code') as string | null) ?? undefined;
			break;
		}
		default:
			return json(Error415JSON, { status: 415 });
	}

	try {
		const database = await getDatabase();
		const user = await getUser({ headers: request.headers });

		const result = await updateComicAuthorByKey(
			{ user, database },
			params.comicCode,
			params.positionCode,
			params.personCode,
			data
		);

		if (!result) return new Response(undefined, { status: 204 });

		const resultKey = result.positionCode + '-' + result.personCode;

		return json(result, {
			headers: {
				Location:
					stringRemoveSuffix(
						new URL(request.url).pathname,
						params.positionCode + '-' + params.personCode
					) + resultKey,
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

		await deleteComicAuthorByKey(
			{ user, database },
			params.comicCode,
			params.positionCode,
			params.personCode
		);

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
