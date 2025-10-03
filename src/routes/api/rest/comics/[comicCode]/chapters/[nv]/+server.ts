import type { RequestHandler } from './$types';
import type { SetComicChapter } from '$lib/model';
import { json } from '@sveltejs/kit';
import { getDatabase, getUser } from '$lib/server/context';
import {
	deleteComicChapterByKey,
	getComicChapterByKey,
	updateComicChapterByKey
} from '$lib/server/service';
import { stringRemoveSuffix, toDate, toNumber } from '$lib/helper';
import { Error415JSON, Error500JSON } from '$lib/api';
import { APIError, DatabaseError, GenericError, NotFoundError } from '$lib/exception';

export const GET: RequestHandler = async ({ params, request }) => {
	try {
		const database = await getDatabase();
		const user = await getUser({ headers: request.headers });

		const paramNV = params.nv.split('+');

		const result = await getComicChapterByKey(
			{ user, database },
			params.comicCode,
			Number(paramNV[0]),
			paramNV[1]
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
	let data: SetComicChapter = {};

	switch (request.headers.get('Content-Type')) {
		case 'application/json':
			data = await request.json();
			break;
		case 'application/x-www-form-urlencoded': {
			const f = await request.formData();
			data.number = toNumber(f.get('number')) ?? undefined;
			data.version = (f.get('version') as string | null) ?? undefined;
			data.releasedAt = toDate(f.get('released_at')) ?? undefined;
			data.volumeNumber = toNumber(f.get('volume_number')) ?? undefined;
			break;
		}
		default:
			return json(Error415JSON, { status: 415 });
	}

	try {
		const database = await getDatabase();
		const user = await getUser({ headers: request.headers });

		const paramNV = params.nv.split('+');

		const result = await updateComicChapterByKey(
			{ user, database },
			params.comicCode,
			Number(paramNV[0]),
			paramNV[1],
			data
		);

		if (!result) return new Response(undefined, { status: 204 });

		const resultKey = String(result.number) + result.version ? '+' + result.version : '';

		return json(result, {
			headers: {
				Location: stringRemoveSuffix(new URL(request.url).pathname, params.nv) + resultKey,
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

		const paramNV = params.nv.split('+');

		await deleteComicChapterByKey(
			{ user, database },
			params.comicCode,
			Number(paramNV[0]),
			paramNV[1]
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
