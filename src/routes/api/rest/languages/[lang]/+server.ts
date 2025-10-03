import type { RequestHandler } from './$types';
import type { SetLanguage } from '$lib/model';
import { json } from '@sveltejs/kit';
import { getDatabase, getUser } from '$lib/server/context';
import { deleteLanguageByKey, getLanguageByKey, updateLanguageByKey } from '$lib/server/service';
import { stringRemoveSuffix } from '$lib/helper';
import { Error415JSON, Error500JSON } from '$lib/api';
import { APIError, DatabaseError, GenericError, NotFoundError } from '$lib/exception';

export const GET: RequestHandler = async ({ params, request }) => {
	try {
		const database = await getDatabase();
		const user = await getUser({ headers: request.headers });

		const result = await getLanguageByKey({ user, database }, params.lang);

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
	let data: SetLanguage = {};

	switch (request.headers.get('Content-Type')) {
		case 'application/json':
			data = await request.json();
			break;
		case 'application/x-www-form-urlencoded': {
			const f = await request.formData();
			data.lang = (f.get('lang') as string | null) ?? undefined;
			data.name = (f.get('name') as string | null) ?? undefined;
			break;
		}
		default:
			return json(Error415JSON, { status: 415 });
	}

	try {
		const database = await getDatabase();
		const user = await getUser({ headers: request.headers });

		const result = await updateLanguageByKey({ user, database }, params.lang, data);

		if (!result) return new Response(undefined, { status: 204 });

		return json(result, {
			headers: {
				Location: stringRemoveSuffix(new URL(request.url).pathname, params.lang) + result.lang,
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

		await deleteLanguageByKey({ user, database }, params.lang);

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
