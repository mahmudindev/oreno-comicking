import * as model from '$lib/model';

export function toBoolean(v: unknown): boolean | null {
	if (v == undefined || v == null) {
		return null;
	}

	return Boolean(v);
}

export function toNumber(v: unknown): number | null {
	if (v == undefined || v == null) {
		return null;
	}

	return Number(v);
}

export function toDate(v: unknown): Date | null {
	if (v == undefined || v == null) {
		return null;
	}

	return new Date(v as number | string);
}

export function isStringArray(v: unknown): v is string[] {
	if (v == undefined || v == null) {
		return false;
	}

	if (!Array.isArray(v)) {
		return false;
	}

	return v.every((v) => typeof v == 'string');
}

export function isStringRecord(v: unknown): v is Record<string, unknown> {
	if (v == undefined || v == null) {
		return false;
	}

	if (typeof v != 'object') {
		return false;
	}

	return 'key' in v;
}

export function isStringRecordArray(v: unknown): v is Record<string, unknown>[] {
	if (v == undefined || v == null) {
		return false;
	}

	if (!Array.isArray(v)) {
		return false;
	}

	return v.every((v) => isStringRecord(v));
}

export function stringRemoveSuffix(v: string, s: string): string {
	if (!v.endsWith(s)) {
		return v;
	}

	return v.substring(0, s.length);
}

export function randomString(
	length: number,
	characters: string = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
): string {
	let result = '';

	for (let i = 0; i < length; i++) {
		result += characters[Math.floor(Math.random() * characters.length)];
	}

	return result;
}

export function parseOrderBys(obs: string[]): model.OrderBy[] {
	return obs.map((ob) => {
		const obs = ob.split(' ');

		if (obs.length > 1 && !obs[1].includes('=')) {
			return { name: obs[0], order: obs[1] };
		}

		const obr: model.OrderBy = { name: obs[0] };

		obs.forEach((oo) => {
			const kv = oo.split('=', 2);

			switch (kv[0].toLowerCase()) {
				case 'order':
					obr.order = kv[1];
					break;
				case 'nulls':
					obr.nulls = kv[1];
					break;
				default:
					if (!obr.custom) obr.custom = {};

					obr.custom[kv[0]] = kv[1] ?? null;
			}
		});

		return obr;
	});
}
