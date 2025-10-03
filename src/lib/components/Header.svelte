<script lang="ts">
	import logo from '$lib/assets/logo.svg';
	import SearchForm from './SearchForm.svelte';
	import Bars3 from './svg/Bars3.svelte';

	export let siteName: string;
	export let navigationLinks: Map<string, string>;
	export let searchAction: string;

	let hiddenNavBar: boolean = true;
</script>

<header class="mx-auto flex w-full flex-wrap items-center justify-between border-b-2 p-4">
	<a href="/" rel="home" class="flex items-center space-x-3 rtl:space-x-reverse">
		<img class="h-12" src={logo} alt={siteName + ' Logo'} />
		<span class="self-center whitespace-nowrap text-2xl font-semibold">{siteName}</span>
	</a>
	<button
		type="button"
		class="inline-flex h-10 w-10 items-center justify-center p-2 md:hidden"
		aria-controls="main-navbar"
		aria-expanded="false"
		on:click={() => (hiddenNavBar = !hiddenNavBar)}><Bars3 /></button
	>
	<nav class:hidden={hiddenNavBar} class="w-full md:block md:w-auto" id="main-navbar">
		<ul class="flex font-medium max-sm:flex-col max-sm:divide-y-2 max-sm:p-3 rtl:space-x-reverse">
			{#each navigationLinks as link}
				<li class="px-2 max-sm:py-2 hover:text-blue-500">
					<a
						href={link[0]}
						target={link[0].startsWith('//') ? '_blank' : undefined}
						class="flex h-full items-center">{link[1]}</a
					>
				</li>
			{/each}
			<li class:hidden={!searchAction} class="px-2 max-sm:border-t-2 max-sm:py-4">
				<div class="flex h-full place-content-center py-3">
					<SearchForm action={searchAction} />
				</div>
			</li>
		</ul>
	</nav>
</header>
