<script lang="ts">
	import type { Comic, ComicCover, ComicTitle, QueryParameter } from '$lib/model';
	import { onMount } from 'svelte';
	import loadingAnimation from '$lib/assets/loading-animation.gif';

	export let comic: Comic;
	export let getComicTitles: (
		comic: Comic,
		param?: QueryParameter,
		out?: Record<string, string | null>
	) => Promise<ComicTitle[]>;
	export let getComicCovers: (
		comic: Comic,
		param?: QueryParameter,
		out?: Record<string, string | null>
	) => Promise<ComicCover[]>;

	let title: string = 'Loading Title...';
	let cover: string = loadingAnimation;

	onMount(async () => {
		const dataTitles = await getComicTitles(comic);
		if (dataTitles.length > 0) {
			title = dataTitles[0].content;
		}

		const dataCovers = await getComicCovers(comic);
		if (dataCovers.length > 1) {
			cover = '//' + dataCovers[0].linkWebsiteHost + dataCovers[0].linkRelativeReference;
		}
	});
</script>

<a class="mb-2 block space-y-1" href="/comics/{comic.code}">
	<div class="card-image border max-sm:h-48 max-sm:w-32 sm:h-72 sm:w-48">
		<img class:card-image-loaded={cover != loadingAnimation} src={cover} alt="{title} Cover" />
	</div>
	<span class="card-title block border-b-2 font-medium max-sm:w-32 sm:w-48">{title}</span>
</a>
