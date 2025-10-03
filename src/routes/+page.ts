import type { PageLoad } from './$types';
import type { Comic, ComicCover, ComicTitle, QueryParameter } from '$lib/model';
import { getComics, getComicCovers, getComicTitles } from '$lib/api';

export const load: PageLoad = async ({ fetch }) => {
	const listComic = function (
		param?: QueryParameter,
		out?: Record<string, string | null>
	): Promise<Comic[]> {
		return getComics(fetch, param, out);
	};

	const listComicTitle = async function (
		comic: Comic,
		param?: QueryParameter,
		out?: Record<string, string | null>
	): Promise<ComicTitle[]> {
		return await getComicTitles(fetch, comic, param, out);
	};

	const listComicCover = async function (
		comic: Comic,
		param?: QueryParameter,
		out?: Record<string, string | null>
	): Promise<ComicCover[]> {
		return await getComicCovers(fetch, comic, param, out);
	};

	return { listComic, listComicTitle, listComicCover };
};
