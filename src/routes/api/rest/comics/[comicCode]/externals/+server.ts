import type { RequestHandler } from './$types';
import type { NewComicExternal } from '$lib/model';
import { json } from '@sveltejs/kit';
import { getDatabase, getUser } from '$lib/server/context';
import { addComicExternal, countComicExternal, listComicExternal } from '$lib/server/service';
import { parseOrderBys, toBoolean, toNumber } from '$lib/helper';
import { Error415JSON, Error500JSON } from '$lib/api';
import { APIError, DatabaseError, GenericError } from '$lib/exception';

export const GET: RequestHandler = async ({ params, request, url }) => {
    const limit = toNumber(url.searchParams.get('limit')) || 10;
    const offset = toNumber(url.searchParams.get('offset')) || undefined;
    const page = toNumber(url.searchParams.get('page')) || undefined;

    const orderBys = parseOrderBys(url.searchParams.getAll('orderBy'));
    const criteria: Record<string, unknown> = {};

    criteria['comicCodes'] = [params.comicCode];

    try {
        const database = await getDatabase();
        const user = await getUser({ headers: request.headers });

        const result = await listComicExternal(
            { user, database },
            { limit, offset, page, criteria, orderBys }
        );
        const totalCount = await countComicExternal({ user, database }, { criteria });

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

export const POST: RequestHandler = async ({ params, request }) => {
    let data: NewComicExternal = {
        comicCode: '',
        linkWebsiteHost: ''
    };

    switch (request.headers.get('Content-Type')) {
        case 'application/json':
            data = await request.json();
            break;
        case 'application/x-www-form-urlencoded': {
            const f = await request.formData();
            data.linkWebsiteHost = f.get('link_website_host') as string;
            data.linkRelativeReference = (f.get('link_relative_reference') as string | null) ?? undefined;
            data.isOfficial = toBoolean(f.get('is_official')) ?? undefined;
            data.isCommunity = toBoolean(f.get('is_community')) ?? undefined;
            break;
        }
        default:
            return json(Error415JSON, { status: 415 });
    }

    data.comicCode = params.comicCode;

    try {
        const database = await getDatabase();
        const user = await getUser({ headers: request.headers });

        const result = await addComicExternal({ user, database }, data);

		const resultKey = encodeURIComponent(result.linkWebsiteHost + result.linkRelativeReference);

        return json(result, {
            headers: {
                Location: new URL(request.url).pathname + '/' + resultKey,
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
