<script lang="ts">
	import '../app.css';
	import favicon from '$lib/assets/favicon.svg';
	import Header from '$lib/components/Header.svelte';

	let { children } = $props();

	const siteName: string = 'ComicKing';
	const navigationLinks: Map<string, string> = new Map();
	const searchAction: string = '';

	navigationLinks.set('/', 'Home');
	navigationLinks.set('/comics', 'Comics');
	navigationLinks.set('//oreno.mahmudin.dev', 'Oreno');

	const date = new Date();
</script>

<svelte:head>
	<link rel="icon" href={favicon} />
</svelte:head>

<div class="container mx-auto flex min-h-screen flex-col bg-white">
	<Header {siteName} {navigationLinks} {searchAction} />
	<main class="flex-1 bg-slate-50">
		{@render children?.()}
	</main>
	<footer class="flex flex-col bg-gray-800 py-2 text-white">
		<span class="self-center">Copyright {date.getFullYear()}</span>
	</footer>
</div>
