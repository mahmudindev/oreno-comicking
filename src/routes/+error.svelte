<script lang="ts">
	import { page } from '$app/stores';
</script>

<div class="flex h-32 items-center justify-center">
	<h1 class="text-2xl"><span class="font-bold">{$page.status}</span>: {$page.error?.message}</h1>
</div>
