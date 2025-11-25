import type { RequestHandler } from './$types';
import type { NewCharacter, ParameterCharacter } from '$lib/model';
import { json } from '@sveltejs/kit';
import { getDatabase, getUser } from '$lib/server/context';
import { addCharacter, countCharacter, listCharacter } from '$lib/server/service';
import { parseOrderBys, toNumber } from '$lib/helper';
import { Error415JSON, Error500JSON } from '$lib/api';
import { APIError, DatabaseError, GenericError } from '$lib/exception';

export const GET: RequestHandler = async ({ request, url }) => {
	const limit = toNumber(url.searchParams.get('limit')) || 10;
	const offset = toNumber(url.searchParams.get('offset')) || undefined;
	const page = toNumber(url.searchParams.get('page')) || undefined;

	const orderBys = parseOrderBys(url.searchParams.getAll('orderBy'));
	const param: ParameterCharacter = {
		criteriaCodes: url.searchParams.getAll('code')
	};

	try {
		const database = await getDatabase();
		const user = await getUser({ headers: request.headers });

		const result = await listCharacter(
			{ user, database },
			{ limit, offset, page, orderBys, ...param }
		);
		const totalCount = await countCharacter({ user, database }, { ...param });

		return json(result, {
			headers: {
				'X-Content-Type-Options': 'nosniff',
				'X-Total-Count': String(totalCount),
				'X-Pagination-Limit': String(limit)
			}
		});
	} catch (e) {
		let result = Error500JSON;
		let status = 500;

		if (e instanceof DatabaseError || e instanceof APIError) {
			result = { message: e.message };
		} else if (e instanceof GenericError) {
			result = { message: e.message };
			status = 400;
		}

		if (e && status == 500) console.log(e);

		return json(result, { status });
	}
};

export const POST: RequestHandler = async ({ request }) => {
	let data: NewCharacter = {
		name: ''
	};

	switch (request.headers.get('Content-Type')) {
		case 'application/json':
			data = await request.json();
			break;
		case 'application/x-www-form-urlencoded': {
			const f = await request.formData();
			data.code = (f.get('code') as string | null) ?? undefined;
			data.name = f.get('name') as string;
			break;
		}
		default:
			return json(Error415JSON, { status: 415 });
	}

	try {
		const database = await getDatabase();
		const user = await getUser({ headers: request.headers });

		const result = await addCharacter({ user, database }, data);

		return json(result, {
			headers: {
				Location: new URL(request.url).pathname + '/' + result.code,
				'X-Content-Type-Options': 'nosniff'
			}
		});
	} catch (e) {
		let result = Error500JSON;
		let status = 500;

		if (e instanceof DatabaseError || e instanceof APIError) {
			result = { message: e.message };
		} else if (e instanceof GenericError) {
			result = { message: e.message };
			status = 400;
		}

		if (e && status == 500) console.log(e);

		return json(result, { status });
	}
};
