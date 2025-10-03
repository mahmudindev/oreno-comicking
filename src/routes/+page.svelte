<script lang="ts">
	import type { PageProps } from './$types';
	import type { Comic } from '$lib/model/model';
	import { onMount } from 'svelte';
	import CardComic from '$lib/components/CardComic.svelte';

	const { data }: PageProps = $props();

	let comics: Comic[] = $state.raw([]);
	let totalComic: number = $state(0);

	let limitComic: number = 20;
	let comicsPage: number = $state(1);

	async function loadComics() {
		const dataComics: Record<string, string | null> = {};

		comics = await data.listComic(
			{
				limit: limitComic,
				page: comicsPage,
				orderBys: [{ name: 'createdAt', order: 'desc' }]
			},
			dataComics
		);

		totalComic = Number(dataComics['Total-Count']);
	}

	onMount(async () => await loadComics());
</script>

<div class="flex h-32 items-center justify-center bg-slate-700 text-white">
	<h1 class="text-center text-4xl font-medium">Comic Catalog</h1>
</div>
<div class="space-y-6 bg-white p-8 max-sm:p-4">
	{#if totalComic > 0}
		<div class="flex flex-wrap justify-center gap-4 py-2">
			{#each comics as comic}
				<CardComic
					{comic}
					getComicTitles={data.listComicTitle}
					getComicCovers={data.listComicCover}
				/>
			{/each}
		</div>
		<div class="p-2 text-center">
			<button
				class="border bg-slate-100 p-2 hover:bg-red-100"
				onclick={() => {
					if (comicsPage > 1) {
						comicsPage--;
						loadComics();
					}
				}}>Prev</button
			>
			<span class="mx-8">{comicsPage}</span>
			<button
				class="border bg-slate-100 p-2 hover:bg-green-100"
				onclick={() => {
					if (comicsPage * limitComic < totalComic) {
						comicsPage++;
						loadComics();
					}
				}}>Next</button
			>
		</div>
	{/if}
</div>
