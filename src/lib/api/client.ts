import type { paths as Paths } from './openapi';
import createClient from 'openapi-fetch';
import { APIError } from '$lib/exception';
import * as model from '$lib/model';

const client = createClient<Paths>({ baseUrl: '/api' });

type fetch = typeof fetch;

export async function getComics(
	fetch: fetch,
	param?: model.QueryParameter,
	out?: Record<string, string | null>
): Promise<model.Comic[]> {
	const result = await client
		.GET('/rest/comics', {
			fetch,
			params: {
				query: {
					limit: param?.limit,
					page: param?.page,
					order_by: param?.orderBys?.map((val) => {
						let orderBy = val.name;

						if (val.order) {
							orderBy += ' order=' + val.order;
						}

						if (val.nulls) {
							orderBy += ' nulls=' + val.nulls;
						}

						return orderBy;
					})
				}
			}
		})
		.catch((e) => {
			throw new APIError('unknown api error', { cause: e });
		});

	if (result.error) {
		throw new APIError(result.error.message);
	}

	if (out) {
		out['Total-Count'] = result.response.headers.get('X-Total-Count');
	}

	return result.data.map((result) => {
		return {
			...result,
			createdAt: new Date(result.createdAt),
			updatedAt: result.updatedAt ? new Date(result.updatedAt) : null,
			publishedFrom: result.publishedFrom ? new Date(result.publishedFrom) : null,
			publishedTo: result.publishedTo ? new Date(result.publishedTo) : null
		};
	});
}

export async function getComicTitles(
	fetch: fetch,
	comic: model.Comic,
	param?: model.QueryParameter,
	out?: Record<string, string | null>
): Promise<model.ComicTitle[]> {
	const result = await client
		.GET('/rest/comics/{comicCode}/titles', {
			fetch,
			params: {
				path: { comicCode: comic.code },
				query: {
					limit: param?.limit,
					page: param?.page,
					order_by: param?.orderBys?.map((val) => {
						let orderBy = val.name;

						if (val.order) {
							orderBy += ' order=' + val.order;
						}

						if (val.nulls) {
							orderBy += ' nulls=' + val.nulls;
						}

						return orderBy;
					})
				}
			}
		})
		.catch((e) => {
			throw new APIError('unknown api error', { cause: e });
		});

	if (result.error) {
		throw new APIError(result.error.message);
	}

	if (out) {
		out['Total-Count'] = result.response.headers.get('X-Total-Count');
	}

	return result.data.map((result) => {
		return {
			...result,
			createdAt: new Date(result.createdAt),
			updatedAt: result.updatedAt ? new Date(result.updatedAt) : null
		};
	});
}

export async function getComicCovers(
	fetch: fetch,
	comic: model.Comic,
	param?: model.QueryParameter,
	out?: Record<string, string | null>
): Promise<model.ComicCover[]> {
	const result = await client
		.GET('/rest/comics/{comicCode}/covers', {
			fetch,
			params: {
				path: { comicCode: comic.code },
				query: {
					limit: param?.limit,
					page: param?.page,
					order_by: param?.orderBys?.map((val) => {
						let orderBy = val.name;

						if (val.order) {
							orderBy += ' order=' + val.order;
						}

						if (val.nulls) {
							orderBy += ' nulls=' + val.nulls;
						}

						return orderBy;
					})
				}
			}
		})
		.catch((e) => {
			throw new APIError('unknown api error', { cause: e });
		});

	if (result.error) {
		throw new APIError(result.error.message);
	}

	if (out) {
		out['Total-Count'] = result.response.headers.get('X-Total-Count');
	}

	return result.data.map((result) => {
		return {
			...result,
			createdAt: new Date(result.createdAt),
			updatedAt: result.updatedAt ? new Date(result.updatedAt) : null
		};
	});
}
