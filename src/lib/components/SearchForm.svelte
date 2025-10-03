<script lang="ts">
	import MagnifyingGlass from './svg/MagnifyingGlass.svelte';

	export let action: string;
	export let placeholder: string = 'Search ...';
	export let name: string = 's';
</script>

<form {action} role="search" class="flex h-full w-full items-center border-2">
	<label class="w-full px-1">
		<input type="search" {placeholder} {name} class="w-full bg-inherit outline-0" />
	</label>
	<button type="submit" class="px-1"><MagnifyingGlass /></button>
</form>
