import type { ColumnType, Expression, Generated, OrderByItemBuilder, SqlBool } from 'kysely';
import type { ULID } from 'ulid';
import { Kysely, PostgresDialect } from 'kysely';
import { Pool } from 'pg';
import { env } from '$env/dynamic/private';
import { HREF, randomString } from '$lib/helper';
import { DatabaseError } from '$lib/exception';
import { ulid } from 'ulid';
import * as model from './model';

export interface Schema {
	language: LanguageTable;
	website: WebsiteTable;
	link: LinkTable;
	character: CharacterTable;
	person: PersonTable;
	magazine: MagazineTable;
	category: CategoryTable;
	category_type: CategoryTypeTable;
	tag: TagTable;
	tag_type: TagTypeTable;
	comic: ComicTable;
	comic_title: ComicTitleTable;
	comic_cover: ComicCoverTable;
	comic_synopsis: ComicSynopsisTable;
	comic_character: ComicCharacterTable;
	comic_author: ComicAuthorTable;
	comic_serialization: ComicSerializationTable;
	comic_external: ComicExternalTable;
	comic_chapter: ComicChapterTable;
	comic_chapter_title: ComicChapterTitleTable;
	comic_volume: ComicVolumeTable;
	comic_volume_title: ComicVolumeTitleTable;
	comic_volume_cover: ComicVolumeCoverTable;
	comic_category: ComicCategoryTable;
	comic_tag: ComicTagTable;
	comic_relation: ComicRelationTable;
	comic_author_position: ComicAuthorPositionTable;
	comic_relation_type: ComicRelationTypeTable;
}

export type DB = Kysely<Schema>;

export class Database {
	static #instance?: DB;

	private constructor() {}

	public static getInstance(initURL?: string, initSchema?: string): DB {
		if (!this.#instance) {
			let database = new Kysely<Schema>({
				dialect: new PostgresDialect({
					pool: new Pool({
						connectionString: initURL
					})
				})
			});

			if (initSchema) {
				database = database.withSchema(initSchema);
			}

			this.#instance = database;
		}

		return this.#instance;
	}
}

const database = Database.getInstance(env.DATABASE_URL, env.DATABASE_SCHEMA);

function orderByItemHelper(ob: OrderByItemBuilder, mob: model.OrderBy): OrderByItemBuilder {
	switch (mob.order?.toLowerCase()) {
		case 'asc':
		case 'ascending':
			ob = ob.asc();
			break;
		case 'desc':
		case 'descending':
			ob = ob.desc();
			break;
	}

	switch (mob.nulls?.toLowerCase()) {
		case 'first':
			ob = ob.nullsFirst();
			break;
		case 'last':
			ob = ob.nullsLast();
			break;
	}

	return ob;
}

function catchExecption(e: unknown): never {
	throw new DatabaseError('unknown database exception', { cause: e });
}

//
// Language
//

interface LanguageTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	lang: string;
	name: string;
}

export async function insertLanguage(db: DB, data: model.NewLanguage): Promise<model.Language> {
	const result = await db
		.insertInto('language')
		.values({
			lang: data.lang,
			name: data.name
		})
		.returningAll()
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		lang: result.lang,
		name: result.name
	};
}

export async function selectLanguage(
	db: DB,
	param: model.ParameterLanguage
): Promise<model.Language[]> {
	let query = db.selectFrom('language').selectAll();

	const ct0 = param.criteriaLangs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('lang', '=', ct0[0]);
		} else {
			query = query.where('lang', 'in', ct0);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'created_at';
					break;
				case 'updatedAt':
					name = 'updated_at';
					break;
				case 'lang':
				case 'name':
					name = v.name;
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('lang');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			lang: result.lang,
			name: result.name
		};
	});
}

export async function selectLanguageByKey(
	db: DB,
	lang: string
): Promise<model.Language | undefined> {
	const result = await db
		.selectFrom('language')
		.where('lang', '=', lang)
		.selectAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		lang: result.lang,
		name: result.name
	};
}

export async function updateLanguageByKey(
	db: DB,
	lang: string,
	data: model.SetLanguage
): Promise<model.Language | undefined> {
	const result = await db
		.updateTable('language')
		.set({
			updated_at: new Date(),
			lang: data.lang,
			name: data.name
		})
		.where('lang', '=', lang)
		.returningAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		lang: result.lang,
		name: result.name
	};
}

export async function deleteLanguageByKey(db: DB, lang: string): Promise<boolean> {
	const result = await db
		.deleteFrom('language')
		.where('lang', '=', lang)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countLanguage(db: DB, param: model.ParameterLanguage): Promise<number> {
	let query = db.selectFrom('language').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaLangs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('lang', '=', ct0[0]);
		} else {
			query = query.where('lang', 'in', ct0);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

//
// Website
//

interface WebsiteTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	host: string;
	name: string | null;
}

export async function insertWebsite(db: DB, data: model.NewWebsite): Promise<model.Website> {
	const result = await db
		.insertInto('website')
		.values({
			host: data.host,
			name: data.name
		})
		.returningAll()
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		host: result.host,
		name: result.name,
		linkCount: 0
	};
}

export async function selectWebsite(
	db: DB,
	param: model.ParameterWebsite
): Promise<model.Website[]> {
	let query = db.selectFrom('website').selectAll();

	const ct0 = param.criteriaHosts ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('host', '=', ct0[0]);
		} else {
			query = query.where('host', 'in', ct0);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'created_at';
					break;
				case 'updatedAt':
					name = 'updated_at';
					break;
				case 'host':
				case 'name':
					name = v.name;
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('host');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			host: result.host,
			name: result.name,
			linkCount: -1
		};
	});
}

export async function selectWebsiteByKey(db: DB, host: string): Promise<model.Website | undefined> {
	const result = await db
		.selectFrom('website')
		.where('host', '=', host)
		.selectAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		host: result.host,
		name: result.name,
		linkCount: -1
	};
}

export async function updateWebsiteByKey(
	db: DB,
	host: string,
	data: model.SetWebsite
): Promise<model.Website | undefined> {
	const result = await db
		.updateTable('website')
		.set({
			updated_at: new Date(),
			host: data.host,
			name: data.name
		})
		.where('host', '=', host)
		.returningAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		host: result.host,
		name: result.name,
		linkCount: -1
	};
}

export async function deleteWebsiteByKey(db: DB, host: string): Promise<boolean> {
	const result = await db
		.deleteFrom('website')
		.where('host', '=', host)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countWebsite(db: DB, param: model.ParameterWebsite): Promise<number> {
	let query = db.selectFrom('website').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaHosts ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('host', '=', ct0[0]);
		} else {
			query = query.where('host', 'in', ct0);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

//
// Link
//

interface LinkTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	website_id: number;
	relative_reference: string;
}

export async function insertLink(db: DB, data: model.NewLink): Promise<model.Link> {
	const result = await db
		.with('cte_website', (db) => {
			return db
				.selectFrom('website')
				.select(['id', 'host', 'name'])
				.where('host', '=', data.websiteHost);
		})
		.insertInto('link')
		.values(({ selectFrom }) => ({
			website_id: selectFrom('cte_website').select('id'),
			relative_reference: data.relativeReference ?? '/'
		}))
		.returning(({ selectFrom }) => [
			'link.id',
			'link.created_at',
			'link.updated_at',
			selectFrom('cte_website').select('host').as('website_host'),
			selectFrom('cte_website').select('name').as('website_name'),
			'link.relative_reference'
		])
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		websiteHost: result.website_host ?? '',
		websiteName: result.website_name,
		relativeReference: result.relative_reference
	};
}

export async function selectLink(db: DB, param: model.ParameterLink): Promise<model.Link[]> {
	let query = db
		.selectFrom('link')
		.innerJoin('website', 'website.id', 'link.website_id')
		.select([
			'link.id',
			'link.created_at',
			'link.updated_at',
			'website.host as website_host',
			'website.name as website_name',
			'link.relative_reference'
		]);

	const ct0 = param.criteriaWebsiteIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('link.website_id', '=', ct0[0]);
		} else {
			query = query.where('link.website_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaWebsiteHosts ?? [];
	if (ct1.length > 0) {
		if (ct1.length == 1) {
			query = query.where('website.host', '=', ct1[0]);
		} else {
			query = query.where('website.host', 'in', ct1);
		}
	}

	const ct2 = param.criteriaRelativeReferences ?? [];
	if (ct2.length > 0) {
		if (ct2.length == 1) {
			query = query.where('link.relative_reference', '=', ct2[0]);
		} else {
			query = query.where('link.relative_reference', 'in', ct2);
		}
	}

	const ct3 = param.criteriaHREFs ?? [];
	if (ct3.length > 0) {
		if (ct3.length == 1) {
			const href = new HREF(ct3[0]);

			query = query.where((eb) =>
				eb.and([
					eb('website.host', '=', href.getHost() ?? ''),
					eb('link.relative_reference', '=', href.getRelativeReference() ?? '')
				])
			);
		} else {
			query = query.where((eb) => {
				const ors: Expression<SqlBool>[] = [];

				ct3.forEach((v) => {
					const href = new HREF(v);

					ors.push(
						eb.and([
							eb('website.host', '=', href.getHost() ?? ''),
							eb('link.relative_reference', '=', href.getRelativeReference() ?? '')
						])
					);
				});

				return eb.or(ors);
			});
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'link.created_at';
					break;
				case 'updatedAt':
					name = 'link.updated_at';
					break;
				case 'websiteHost':
					name = 'website.host';
					break;
				case 'websiteName':
					name = 'website.name';
					break;
				case 'relativeReference':
					name = 'link.relative_reference';
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('website.host');
		query = query.orderBy('link.relative_reference');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			websiteHost: result.website_host,
			websiteName: result.website_name,
			relativeReference: result.relative_reference
		};
	});
}

export async function selectLinkByKey(
	db: DB,
	websiteHost: string,
	relativeReference: string
): Promise<model.Link | undefined> {
	const result = await db
		.selectFrom('link')
		.innerJoin('website', 'website.id', 'link.website_id')
		.where((eb) =>
			eb.and([
				eb('website.host', '=', websiteHost),
				eb('link.relative_reference', '=', relativeReference)
			])
		)
		.select([
			'link.id',
			'link.created_at',
			'link.updated_at',
			'website.host as website_host',
			'website.name as website_name',
			'link.relative_reference'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		websiteHost: result.website_host,
		websiteName: result.website_name,
		relativeReference: result.relative_reference
	};
}

export async function updateLinkByKey(
	db: DB,
	websiteHost: string,
	relativeReference: string,
	data: model.SetLink
): Promise<model.Link | undefined> {
	const result = await db
		.updateTable('link')
		.innerJoin('website', 'website.id', 'link.website_id')
		.set(({ selectFrom }) => ({
			updated_at: new Date(),
			website_id: data.websiteHost
				? selectFrom('website').select('id').where('host', '=', data.websiteHost)
				: undefined,
			relative_reference: data.relativeReference
		}))
		.where((eb) =>
			eb.and([
				eb('website.host', '=', websiteHost),
				eb('link.relative_reference', '=', relativeReference)
			])
		)
		.returning([
			'link.id',
			'link.created_at',
			'link.updated_at',
			'website.host as website_host',
			'website.name as website_name',
			'link.relative_reference'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		websiteHost: result.website_host,
		websiteName: result.website_name,
		relativeReference: result.relative_reference
	};
}

export async function deleteLinkByKey(
	db: DB,
	websiteHost: string,
	relativeReference: string
): Promise<boolean> {
	const result = await db
		.deleteFrom('link')
		.innerJoin('website', 'website.id', 'link.website_id')
		.where((eb) =>
			eb.and([
				eb('website.host', '=', websiteHost),
				eb('link.relative_reference', '=', relativeReference)
			])
		)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countLink(db: DB, param: model.ParameterLink) {
	let query = db.selectFrom('link').select((eb) => eb.fn.countAll<number>().as('count'));

	let join0 = false;
	const jn0 = function () {
		if (join0) return;

		query = query.innerJoin('website', 'website.id', 'link.website_id');
		join0 = true;
	};

	const ct0 = param.criteriaWebsiteIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('website_id', '=', ct0[0]);
		} else {
			query = query.where('website_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaWebsiteHosts ?? [];
	if (ct1.length > 0) {
		const { ref } = db.dynamic;

		jn0();

		if (ct1.length == 1) {
			query = query.where(ref('website.host'), '=', ct1[0]);
		} else {
			query = query.where(ref('website.host'), 'in', ct1);
		}
	}

	const ct2 = param.criteriaRelativeReferences ?? [];
	if (ct2.length > 0) {
		if (ct2.length == 1) {
			query = query.where('relative_reference', '=', ct2[0]);
		} else {
			query = query.where('relative_reference', 'in', ct2);
		}
	}

	const ct3 = param.criteriaHREFs ?? [];
	if (ct3.length > 0) {
		const { ref } = db.dynamic;

		jn0();

		if (ct3.length == 1) {
			const href = new HREF(ct3[0]);

			query = query.where((eb) =>
				eb.and([
					eb(ref('website.host'), '=', href.getHost() ?? ''),
					eb('link.relative_reference', '=', href.getRelativeReference() ?? '')
				])
			);
		} else {
			query = query.where((eb) => {
				const ors: Expression<SqlBool>[] = [];

				ct3.forEach((v) => {
					const href = new HREF(v);

					ors.push(
						eb.and([
							eb(ref('website.host'), '=', href.getHost() ?? ''),
							eb('link.relative_reference', '=', href.getRelativeReference() ?? '')
						])
					);
				});

				return eb.or(ors);
			});
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

//
// Character
//

interface CharacterTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	code: string;
	name: string;
}

export async function insertCharacter(db: DB, data: model.NewCharacter): Promise<model.Character> {
	const result = await db
		.insertInto('character')
		.values({
			code: data.code ?? randomString(12),
			name: data.name
		})
		.returningAll()
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function selectCharacter(
	db: DB,
	param: model.ParameterCharacter
): Promise<model.Character[]> {
	let query = db.selectFrom('character').selectAll();

	const ct0 = param.criteriaCodes ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('code', '=', ct0[0]);
		} else {
			query = query.where('code', 'in', ct0);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'created_at';
					break;
				case 'updatedAt':
					name = 'updated_at';
					break;
				case 'code':
				case 'name':
					name = v.name;
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('code');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			code: result.code,
			name: result.name
		};
	});
}

export async function selectCharacterByKey(
	db: DB,
	code: string
): Promise<model.Character | undefined> {
	const result = await db
		.selectFrom('character')
		.where('code', '=', code)
		.selectAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function updateCharacterByKey(
	db: DB,
	code: string,
	data: model.SetCharacter
): Promise<model.Character | undefined> {
	const result = await db
		.updateTable('character')
		.set({
			updated_at: new Date(),
			code: data.code,
			name: data.name
		})
		.where('code', '=', code)
		.returningAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function deleteCharacterByKey(db: DB, code: string): Promise<boolean> {
	const result = await db
		.deleteFrom('character')
		.where('code', '=', code)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countCharacter(db: DB, param: model.ParameterCharacter): Promise<number> {
	let query = db.selectFrom('character').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaCodes ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('code', '=', ct0[0]);
		} else {
			query = query.where('code', 'in', ct0);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

//
// Person
//

interface PersonTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	code: string;
	name: string;
}

export async function insertPerson(db: DB, data: model.NewPerson): Promise<model.Person> {
	const result = await db
		.insertInto('person')
		.values({
			code: data.code ?? randomString(12),
			name: data.name
		})
		.returningAll()
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function selectPerson(db: DB, param: model.ParameterPerson): Promise<model.Person[]> {
	let query = db.selectFrom('person').selectAll();

	const ct0 = param.criteriaCodes ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('code', '=', ct0[0]);
		} else {
			query = query.where('code', 'in', ct0);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'created_at';
					break;
				case 'updatedAt':
					name = 'updated_at';
					break;
				case 'code':
				case 'name':
					name = v.name;
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('code');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			code: result.code,
			name: result.name
		};
	});
}

export async function selectPersonByKey(db: DB, code: string): Promise<model.Person | undefined> {
	const result = await db
		.selectFrom('person')
		.where('code', '=', code)
		.selectAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function updatePersonByKey(
	db: DB,
	code: string,
	data: model.SetPerson
): Promise<model.Person | undefined> {
	const result = await db
		.updateTable('person')
		.set({
			updated_at: new Date(),
			code: data.code,
			name: data.name
		})
		.where('code', '=', code)
		.returningAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function deletePersonByKey(db: DB, code: string): Promise<boolean> {
	const result = await db
		.deleteFrom('person')
		.where('code', '=', code)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countPerson(db: DB, param: model.ParameterPerson): Promise<number> {
	let query = db.selectFrom('person').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaCodes ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('code', '=', ct0[0]);
		} else {
			query = query.where('code', 'in', ct0);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

//
// Magazine
//

interface MagazineTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	code: string;
	name: string;
}

export async function insertMagazine(db: DB, data: model.NewMagazine): Promise<model.Magazine> {
	const result = await db
		.insertInto('magazine')
		.values({
			code: data.code ?? randomString(12),
			name: data.name
		})
		.returningAll()
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function selectMagazine(
	db: DB,
	param: model.ParameterMagazine
): Promise<model.Magazine[]> {
	let query = db.selectFrom('magazine').selectAll();

	const ct0 = param.criteriaCodes ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('code', '=', ct0[0]);
		} else {
			query = query.where('code', 'in', ct0);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'created_at';
					break;
				case 'updatedAt':
					name = 'updated_at';
					break;
				case 'code':
				case 'name':
					name = v.name;
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('code');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			code: result.code,
			name: result.name
		};
	});
}

export async function selectMagazineByKey(
	db: DB,
	code: string
): Promise<model.Magazine | undefined> {
	const result = await db
		.selectFrom('magazine')
		.where('code', '=', code)
		.selectAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function updateMagazineByKey(
	db: DB,
	code: string,
	data: model.SetMagazine
): Promise<model.Magazine | undefined> {
	const result = await db
		.updateTable('magazine')
		.set({
			updated_at: new Date(),
			code: data.code,
			name: data.name
		})
		.where('code', '=', code)
		.returningAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function deleteMagazineByKey(db: DB, code: string): Promise<boolean> {
	const result = await db
		.deleteFrom('magazine')
		.where('code', '=', code)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countMagazine(db: DB, param: model.ParameterMagazine): Promise<number> {
	let query = db.selectFrom('magazine').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaCodes ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('code', '=', ct0[0]);
		} else {
			query = query.where('code', 'in', ct0);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

//
// Category
//

interface CategoryTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	type_id: number;
	code: string;
	name: string;
	parent_id: number | null;
}

export async function insertCategory(db: DB, data: model.NewCategory): Promise<model.Category> {
	const result = await db
		.with('cte_category_type', (db) => {
			return db
				.selectFrom('category_type')
				.select(['id', 'code'])
				.where('code', '=', data.typeCode);
		})
		.with('cte_parent', (db) => {
			return db
				.selectFrom('category')
				.select(['id', 'code'])
				.where('code', '=', data.parentCode ?? '');
		})
		.insertInto('category')
		.values(({ selectFrom }) => ({
			type_id: selectFrom('cte_category_type').select('id'),
			code: data.code,
			name: data.name,
			parent_id: selectFrom('cte_parent').select('id')
		}))
		.returning(({ selectFrom }) => [
			'category.id',
			'category.created_at',
			'category.updated_at',
			selectFrom('cte_category_type').select('code').as('type_code'),
			'category.code',
			'category.name',
			selectFrom('cte_parent').select('code').as('parent_code')
		])
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		typeCode: result.type_code ?? '',
		code: result.code,
		name: result.name,
		parentCode: result.parent_code,
		childCount: 0
	};
}

export async function selectCategory(
	db: DB,
	param: model.ParameterCategory
): Promise<model.Category[]> {
	let query = db
		.selectFrom('category')
		.innerJoin('category_type', 'category_type.id', 'category.type_id')
		.innerJoin('category as parent', 'parent.id', 'category.parent_id')
		.select([
			'category.id',
			'category.created_at',
			'category.updated_at',
			'category_type.code as type_code',
			'category.code',
			'category.name',
			'parent.code as parent_code'
		]);

	const ct0 = param.criteriaTypeIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('category.type_id', '=', ct0[0]);
		} else {
			query = query.where('category.type_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaTypeCodes ?? [];
	if (ct1.length > 0) {
		if (ct1.length == 1) {
			query = query.where('category_type.code', '=', ct1[0]);
		} else {
			query = query.where('category_type.code', 'in', ct1);
		}
	}

	const ct2 = param.criteriaIDs ?? [];
	if (ct2.length > 0) {
		if (ct2.length == 1) {
			query = query.where('category.id', '=', ct2[0]);
		} else {
			query = query.where('category.id', 'in', ct2);
		}
	}

	const ct3 = param.criteriaCodes ?? [];
	if (ct3.length > 0) {
		if (ct3.length == 1) {
			query = query.where('category.code', '=', ct3[0]);
		} else {
			query = query.where('category.code', 'in', ct3);
		}
	}

	const ct4 = param.criteriaTypeCodeCodes ?? [];
	if (ct4.length > 0) {
		if (ct3.length == 1) {
			const typeCodeCode = ct4[0].split(':', 2);

			query = query.where((eb) =>
				eb.and([
					eb('category_type.code', '=', typeCodeCode[0]),
					eb('category.code', '=', typeCodeCode[1])
				])
			);
		} else {
			query = query.where((eb) => {
				const ors: Expression<SqlBool>[] = [];

				ct4.forEach((v) => {
					const typeCodeCode = v.split(':', 2);

					ors.push(
						eb.and([
							eb('category_type.code', '=', typeCodeCode[0]),
							eb('category.code', '=', typeCodeCode[1]),
						])
					);
				});

				return eb.or(ors);
			});
		}
	}

	const ct5 = param.criteriaParentIDs ?? [];
	if (ct5.length > 0) {
		if (ct5.length == 1) {
			query = query.where('category.parent_id', '=', ct5[0]);
		} else {
			query = query.where('category.parent_id', 'in', ct5);
		}
	}

	const ct6 = param.criteriaParentCodes ?? [];
	if (ct6.length > 0) {
		if (ct6.length == 1) {
			query = query.where('parent.code', '=', ct6[0]);
		} else {
			query = query.where('parent.code', 'in', ct6);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'category.created_at';
					break;
				case 'updatedAt':
					name = 'category.updated_at';
					break;
				case 'typeCode':
					name = 'category_type.code';
					break;
				case 'parentCode':
					name = 'parent.code';
					break;
				case 'code':
				case 'name':
					name = 'category.' + v.name;
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('category_type.code');
		query = query.orderBy('category.code');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			typeCode: result.type_code,
			code: result.code,
			name: result.name,
			parentCode: result.parent_code,
			childCount: -1
		};
	});
}

export async function selectCategoryByKey(
	db: DB,
	typeCode: string,
	code: string
): Promise<model.Category | undefined> {
	const result = await db
		.selectFrom('category')
		.innerJoin('category_type', 'category_type.id', 'category.type_id')
		.innerJoin('category as parent', 'parent.id', 'category.parent_id')
		.where('category_type.code', '=', typeCode)
		.where('category.code', '=', code)
		.select([
			'category.id',
			'category.created_at',
			'category.updated_at',
			'category_type.code as type_code',
			'category.code',
			'category.name',
			'parent.code as parent_code'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		typeCode: result.type_code,
		code: result.code,
		name: result.name,
		parentCode: result.parent_code,
		childCount: -1
	};
}

export async function updateCategoryByKey(
	db: DB,
	typeCode: string,
	code: string,
	data: model.SetCategory
): Promise<model.Category | undefined> {
	const result = await db
		.updateTable('category')
		.innerJoin('category_type', 'category_type.id', 'category.type_id')
		.innerJoin('category as parent', 'parent.id', 'category.parent_id')
		.set(({ selectFrom }) => ({
			updated_at: new Date(),
			type_id: data.typeCode
				? selectFrom('category_type').select('id').where('code', '=', data.typeCode)
				: undefined,
			code: data.code,
			name: data.name,
			parent_id: data.parentCode
				? selectFrom('category').select('id').where('code', '=', data.parentCode)
				: undefined
		}))
		.where('category_type.code', '=', typeCode)
		.where('category.code', '=', code)
		.returning([
			'category.id',
			'category.created_at',
			'category.updated_at',
			'category_type.code as type_code',
			'category.code',
			'category.name',
			'parent.code as parent_code'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		typeCode: result.type_code,
		code: result.code,
		name: result.name,
		parentCode: result.parent_code,
		childCount: -1
	};
}

export async function deleteCategoryByKey(
	db: DB,
	typeCode: string,
	code: string
): Promise<boolean> {
	const result = await db
		.deleteFrom('category')
		.innerJoin('category_type', 'category_type.id', 'category.type_id')
		.where('category_type.code', '=', typeCode)
		.where('code', '=', code)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countCategory(db: DB, param: model.ParameterCategory): Promise<number> {
	let query = db.selectFrom('category').select((eb) => eb.fn.countAll<number>().as('count'));

	let join0 = false;
	const jn0 = function () {
		if (join0) return;

		query = query.innerJoin('category_type', 'category_type.id', 'category.type_id');
		join0 = true;
	};

	const ct0 = param.criteriaTypeIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('category.type_id', '=', ct0[0]);
		} else {
			query = query.where('category.type_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaTypeCodes ?? [];
	if (ct1.length > 0) {
		const { ref } = db.dynamic;

		jn0();

		if (ct1.length == 1) {
			query = query.where(ref('category_type.code'), '=', ct1[0]);
		} else {
			query = query.where(ref('category_type.code'), 'in', ct1);
		}
	}

	const ct2 = param.criteriaIDs ?? [];
	if (ct2.length > 0) {
		if (ct2.length == 1) {
			query = query.where('category.id', '=', ct2[0]);
		} else {
			query = query.where('category.id', 'in', ct2);
		}
	}

	const ct3 = param.criteriaCodes ?? [];
	if (ct3.length > 0) {
		if (ct3.length == 1) {
			query = query.where('category.code', '=', ct3[0]);
		} else {
			query = query.where('category.code', 'in', ct3);
		}
	}

	const ct4 = param.criteriaTypeCodeCodes ?? [];
	if (ct4.length > 0) {
		const { ref } = db.dynamic;

		jn0();

		if (ct3.length == 1) {
			const typeCodeCode = ct4[0].split(':', 2);

			query = query.where((eb) =>
				eb.and([
					eb(ref('category_type.code'), '=', typeCodeCode[0]),
					eb('category.code', '=', typeCodeCode[1])
				])
			);
		} else {
			query = query.where((eb) => {
				const ors: Expression<SqlBool>[] = [];

				ct4.forEach((v) => {
					const typeCodeCode = v.split(':', 2);

					ors.push(
						eb.and([
							eb(ref('category_type.code'), '=', typeCodeCode[0]),
							eb('category.code', '=', typeCodeCode[1])
						])
					);
				});

				return eb.or(ors);
			});
		}
	}

	const ct5 = param.criteriaParentIDs ?? [];
	if (ct5.length > 0) {
		if (ct5.length == 1) {
			query = query.where('category.parent_id', '=', ct5[0]);
		} else {
			query = query.where('category.parent_id', 'in', ct5);
		}
	}

	const ct6 = param.criteriaParentCodes ?? [];
	if (ct6.length > 0) {
		const querx = query.innerJoin('category as parent', 'parent.id', 'category.parent_id');

		if (ct6.length == 1) {
			query = querx.where('parent.code', '=', ct6[0]);
		} else {
			query = querx.where('parent.code', 'in', ct6);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

// + Category Type

interface CategoryTypeTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	code: string;
	name: string;
}

export async function insertCategoryType(
	db: DB,
	data: model.NewCategoryType
): Promise<model.CategoryType> {
	const result = await db
		.insertInto('category_type')
		.values({
			code: data.code,
			name: data.name
		})
		.returningAll()
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function selectCategoryType(
	db: DB,
	param: model.ParameterCategoryType
): Promise<model.CategoryType[]> {
	let query = db.selectFrom('category_type').selectAll();

	const ct0 = param.criteriaCodes ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('code', '=', ct0[0]);
		} else {
			query = query.where('code', 'in', ct0);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'created_at';
					break;
				case 'updatedAt':
					name = 'updated_at';
					break;
				case 'code':
				case 'name':
					name = v.name;
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('code');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			code: result.code,
			name: result.name
		};
	});
}

export async function selectCategoryTypeByKey(
	db: DB,
	code: string
): Promise<model.CategoryType | undefined> {
	const result = await db
		.selectFrom('category_type')
		.where('code', '=', code)
		.selectAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function updateCategoryTypeByKey(
	db: DB,
	code: string,
	data: model.SetCategoryType
): Promise<model.CategoryType | undefined> {
	const result = await db
		.updateTable('category_type')
		.set({
			updated_at: new Date(),
			code: data.code,
			name: data.name
		})
		.where('code', '=', code)
		.returningAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function deleteCategoryTypeByKey(db: DB, code: string): Promise<boolean> {
	const result = await db
		.deleteFrom('category_type')
		.where('code', '=', code)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countCategoryType(
	db: DB,
	param: model.ParameterCategoryType
): Promise<number> {
	let query = db.selectFrom('category_type').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaCodes ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('code', '=', ct0[0]);
		} else {
			query = query.where('code', 'in', ct0);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

//
// Tag
//

interface TagTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	type_id: number;
	code: string;
	name: string;
}

export async function insertTag(db: DB, data: model.NewTag): Promise<model.Tag> {
	const result = await db
		.with('cte_tag_type', (db) => {
			return db.selectFrom('tag_type').select(['id', 'code']).where('code', '=', data.typeCode);
		})
		.insertInto('tag')
		.values(({ selectFrom }) => ({
			type_id: selectFrom('cte_tag_type').select('id'),
			code: data.code,
			name: data.name
		}))
		.returning(({ selectFrom }) => [
			'tag.id',
			'tag.created_at',
			'tag.updated_at',
			selectFrom('cte_tag_type').select('code').as('type_code'),
			'tag.code',
			'tag.name'
		])
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		typeCode: result.type_code ?? '',
		code: result.code,
		name: result.name
	};
}

export async function selectTag(db: DB, param: model.ParameterTag): Promise<model.Tag[]> {
	let query = db
		.selectFrom('tag')
		.innerJoin('tag_type', 'tag_type.id', 'tag.type_id')
		.select([
			'tag.id',
			'tag.created_at',
			'tag.updated_at',
			'tag_type.code as type_code',
			'tag.code',
			'tag.name'
		]);

	const ct0 = param.criteriaTypeIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('tag.type_id', '=', ct0[0]);
		} else {
			query = query.where('tag.type_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaTypeCodes ?? [];
	if (ct1.length > 0) {
		if (ct1.length == 1) {
			query = query.where('tag_type.code', '=', ct1[0]);
		} else {
			query = query.where('tag_type.code', 'in', ct1);
		}
	}

	const ct2 = param.criteriaIDs ?? [];
	if (ct2.length > 0) {
		if (ct2.length == 1) {
			query = query.where('tag.id', '=', ct2[0]);
		} else {
			query = query.where('tag.id', 'in', ct2);
		}
	}

	const ct3 = param.criteriaCodes ?? [];
	if (ct3.length > 0) {
		if (ct3.length == 1) {
			query = query.where('tag.code', '=', ct3[0]);
		} else {
			query = query.where('tag.code', 'in', ct3);
		}
	}

	const ct4 = param.criteriaTypeCodeCodes ?? [];
	if (ct4.length > 0) {
		if (ct3.length == 1) {
			const typeCodeCode = ct4[0].split(':', 2);

			query = query.where((eb) =>
				eb.and([
					eb('tag_type.code', '=', typeCodeCode[0]),
					eb('tag.code', '=', typeCodeCode[1])
				])
			);
		} else {
			query = query.where((eb) => {
				const ors: Expression<SqlBool>[] = [];

				ct4.forEach((v) => {
					const typeCodeCode = v.split(':', 2);

					ors.push(
						eb.and([
							eb('tag_type.code', '=', typeCodeCode[0]),
							eb('tag.code', '=', typeCodeCode[1]),
						])
					);
				});

				return eb.or(ors);
			});
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'tag.created_at';
					break;
				case 'updatedAt':
					name = 'tag.updated_at';
					break;
				case 'typeCode':
					name = 'tag_type.code';
					break;
				case 'code':
				case 'name':
					name = 'tag.' + v.name;
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('tag_type.code');
		query = query.orderBy('tag.code');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			typeCode: result.type_code,
			code: result.code,
			name: result.name
		};
	});
}

export async function selectTagByKey(
	db: DB,
	typeCode: string,
	code: string
): Promise<model.Tag | undefined> {
	const result = await db
		.selectFrom('tag')
		.innerJoin('tag_type', 'tag_type.id', 'tag.type_id')
		.where('tag_type.code', '=', typeCode)
		.where('tag.code', '=', code)
		.select([
			'tag.id',
			'tag.created_at',
			'tag.updated_at',
			'tag_type.code as type_code',
			'tag.code',
			'tag.name'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		typeCode: result.type_code,
		code: result.code,
		name: result.name
	};
}

export async function updateTagByKey(
	db: DB,
	typeCode: string,
	code: string,
	data: model.SetTag
): Promise<model.Tag | undefined> {
	const result = await db
		.updateTable('tag')
		.innerJoin('tag_type', 'tag_type.id', 'tag.type_id')
		.set(({ selectFrom }) => ({
			updated_at: new Date(),
			type_id: data.typeCode
				? selectFrom('tag_type').select('id').where('code', '=', data.typeCode)
				: undefined,
			code: data.code,
			name: data.name
		}))
		.where('tag_type.code', '=', typeCode)
		.where('tag.code', '=', code)
		.returning([
			'tag.id',
			'tag.created_at',
			'tag.updated_at',
			'tag_type.code as type_code',
			'tag.code',
			'tag.name'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		typeCode: result.type_code,
		code: result.code,
		name: result.name
	};
}

export async function deleteTagByKey(db: DB, typeCode: string, code: string): Promise<boolean> {
	const result = await db
		.deleteFrom('tag')
		.innerJoin('tag_type', 'tag_type.id', 'tag.type_id')
		.where('tag_type.code', '=', typeCode)
		.where('code', '=', code)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countTag(db: DB, param: model.ParameterTag): Promise<number> {
	let query = db.selectFrom('tag').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaTypeIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('tag.type_id', '=', ct0[0]);
		} else {
			query = query.where('tag.type_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaTypeCodes ?? [];
	if (ct1.length > 0) {
		const querx = query.innerJoin('tag_type', 'tag_type.id', 'tag.type_id');

		if (ct1.length == 1) {
			query = querx.where('tag_type.code', '=', ct1[0]);
		} else {
			query = querx.where('tag_type.code', 'in', ct1);
		}
	}

	const ct2 = param.criteriaIDs ?? [];
	if (ct2.length > 0) {
		if (ct2.length == 1) {
			query = query.where('tag.id', '=', ct2[0]);
		} else {
			query = query.where('tag.id', 'in', ct2);
		}
	}

	const ct3 = param.criteriaCodes ?? [];
	if (ct3.length > 0) {
		if (ct3.length == 1) {
			query = query.where('tag.code', '=', ct3[0]);
		} else {
			query = query.where('tag.code', 'in', ct3);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

// + Tag Type

interface TagTypeTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	code: string;
	name: string;
}

export async function insertTagType(db: DB, data: model.NewTagType): Promise<model.TagType> {
	const result = await db
		.insertInto('tag_type')
		.values({
			code: data.code,
			name: data.name
		})
		.returningAll()
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function selectTagType(
	db: DB,
	param: model.ParameterTagType
): Promise<model.TagType[]> {
	let query = db.selectFrom('tag_type').selectAll();

	const ct0 = param.criteriaCodes ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('code', '=', ct0[0]);
		} else {
			query = query.where('code', 'in', ct0);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'created_at';
					break;
				case 'updatedAt':
					name = 'updated_at';
					break;
				case 'code':
				case 'name':
					name = v.name;
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('code');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			code: result.code,
			name: result.name
		};
	});
}

export async function selectTagTypeByKey(db: DB, code: string): Promise<model.TagType | undefined> {
	const result = await db
		.selectFrom('tag_type')
		.where('code', '=', code)
		.selectAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function updateTagTypeByKey(
	db: DB,
	code: string,
	data: model.SetTagType
): Promise<model.TagType | undefined> {
	const result = await db
		.updateTable('tag_type')
		.set({
			updated_at: new Date(),
			code: data.code,
			name: data.name
		})
		.where('code', '=', code)
		.returningAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function deleteTagTypeByKey(db: DB, code: string): Promise<boolean> {
	const result = await db
		.deleteFrom('tag_type')
		.where('code', '=', code)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countTagType(db: DB, param: model.ParameterTagType): Promise<number> {
	let query = db.selectFrom('tag_type').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaCodes ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('code', '=', ct0[0]);
		} else {
			query = query.where('code', 'in', ct0);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

//
// Comic
//

interface ComicTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	code: string;
	published_from: Date | null;
	published_to: Date | null;
	total_chapter: number | null;
	total_volume: number | null;
	nsfw: number | null;
	nsfl: number | null;
}

export async function insertComic(db: DB, data: model.NewComic): Promise<model.Comic> {
	const result = await db
		.insertInto('comic')
		.values({
			code: data.code ?? randomString(12),
			published_from: data.publishedFrom,
			published_to: data.publishedTo,
			total_chapter: data.totalChapter,
			total_volume: data.totalVolume,
			nsfw: data.nsfw,
			nsfl: data.nsfl
		})
		.returningAll()
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		publishedFrom: result.published_from,
		publishedTo: result.published_to,
		totalChapter: result.total_chapter,
		totalVolume: result.total_volume,
		nsfw: result.nsfw,
		nsfl: result.nsfl,
		titleCount: 0,
		coverCount: 0,
		synopsisCount: 0,
		characterCount: 0,
		authorCount: 0,
		serializationCount: 0,
		externalCount: 0,
		chapterCount: 0,
		categoryCount: 0,
		tagCount: 0,
		relationCount: 0
	};
}

export async function selectComic(db: DB, param: model.ParameterComic): Promise<model.Comic[]> {
	let query = db.selectFrom('comic').selectAll();

	const ct0 = param.criteriaCodes ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('code', '=', ct0[0]);
		} else {
			query = query.where('code', 'in', ct0);
		}
	}

	const ct1 = param.criteriaExternals ?? [];
	if (ct1.length > 0) {
		//
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'created_at';
					break;
				case 'updatedAt':
					name = 'updated_at';
					break;
				case 'publishedFrom':
					name = 'published_from';
					break;
				case 'publishedTo':
					name = 'published_to';
					break;
				case 'totalChapter':
					name = 'total_chapter';
					break;
				case 'totalVolume':
					name = 'total_volume';
					break;
				case 'chapterCreatedAt':
					name = 'comic_chapter.created_at';
					break;
				case 'code':
				case 'nsfw':
				case 'nsfl':
					name = v.name;
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('code');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			code: result.code,
			publishedFrom: result.published_from,
			publishedTo: result.published_to,
			totalChapter: result.total_chapter,
			totalVolume: result.total_volume,
			nsfw: result.nsfw,
			nsfl: result.nsfl,
			titleCount: -1,
			coverCount: -1,
			synopsisCount: -1,
			characterCount: -1,
			authorCount: -1,
			serializationCount: -1,
			externalCount: -1,
			chapterCount: -1,
			categoryCount: -1,
			tagCount: -1,
			relationCount: -1
		};
	});
}

export async function selectComicByKey(db: DB, code: string): Promise<model.Comic | undefined> {
	const result = await db
		.selectFrom('comic')
		.where('code', '=', code)
		.selectAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		publishedFrom: result.published_from,
		publishedTo: result.published_to,
		totalChapter: result.total_chapter,
		totalVolume: result.total_volume,
		nsfw: result.nsfw,
		nsfl: result.nsfl,
		titleCount: -1,
		coverCount: -1,
		synopsisCount: -1,
		characterCount: -1,
		authorCount: -1,
		serializationCount: -1,
		externalCount: -1,
		chapterCount: -1,
		categoryCount: -1,
		tagCount: -1,
		relationCount: -1
	};
}

export async function updateComicByKey(
	db: DB,
	code: string,
	data: model.SetComic
): Promise<model.Comic | undefined> {
	const result = await db
		.updateTable('comic')
		.set({
			updated_at: new Date(),
			code: data.code,
			published_from: data.publishedFrom,
			published_to: data.publishedTo,
			total_chapter: data.totalChapter,
			total_volume: data.totalVolume,
			nsfw: data.nsfw,
			nsfl: data.nsfl
		})
		.where('code', '=', code)
		.returningAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		publishedFrom: result.published_from,
		publishedTo: result.published_to,
		totalChapter: result.total_chapter,
		totalVolume: result.total_volume,
		nsfw: result.nsfw,
		nsfl: result.nsfl,
		titleCount: -1,
		coverCount: -1,
		synopsisCount: -1,
		characterCount: -1,
		authorCount: -1,
		serializationCount: -1,
		externalCount: -1,
		chapterCount: -1,
		categoryCount: -1,
		tagCount: -1,
		relationCount: -1
	};
}

export async function deleteComicByKey(db: DB, code: string): Promise<boolean> {
	const result = await db
		.deleteFrom('comic')
		.where('code', '=', code)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countComic(db: DB, param: model.ParameterComic): Promise<number> {
	let query = db.selectFrom('comic').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaCodes ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('code', '=', ct0[0]);
		} else {
			query = query.where('code', 'in', ct0);
		}
	}

	const ct1 = param.criteriaExternals ?? [];
	if (ct1.length > 0) {
		//
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

// Comic Title

interface ComicTitleTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	comic_id: number;
	ulid: ULID;
	language_id: number;
	content: string;
	is_synonym: boolean | null;
	is_latinized: boolean | null;
}

export async function insertComicTitle(
	db: DB,
	data: model.NewComicTitle
): Promise<model.ComicTitle> {
	const result = await db
		.with('cte_comic', (db) => {
			return db.selectFrom('comic').select(['id', 'code']).where('code', '=', data.comicCode);
		})
		.with('cte_language', (db) => {
			return db.selectFrom('language').select(['id', 'lang']).where('lang', '=', data.languageLang);
		})
		.insertInto('comic_title')
		.values(({ selectFrom }) => ({
			comic_id: selectFrom('cte_comic').select('id'),
			ulid: ulid(),
			language_id: selectFrom('cte_language').select('id'),
			content: data.content,
			is_synonym: data.isSynonym,
			is_latinized: data.isLatinized
		}))
		.returning(({ selectFrom }) => [
			'comic_title.id',
			'comic_title.created_at',
			'comic_title.updated_at',
			selectFrom('cte_comic').select('code').as('comic_code'),
			'comic_title.ulid',
			selectFrom('cte_language').select('lang').as('language_lang'),
			'comic_title.content',
			'comic_title.is_synonym',
			'comic_title.is_latinized'
		])
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code ?? '',
		ulid: result.ulid,
		languageLang: result.language_lang ?? '',
		content: result.content,
		isSynonym: result.is_synonym,
		isLatinized: result.is_latinized
	};
}

export async function selectComicTitle(
	db: DB,
	param: model.ParameterComicTitle
): Promise<model.ComicTitle[]> {
	let query = db
		.selectFrom('comic_title')
		.innerJoin('comic', 'comic.id', 'comic_title.comic_id')
		.innerJoin('language', 'language.id', 'comic_title.language_id')
		.select([
			'comic_title.id',
			'comic_title.created_at',
			'comic_title.updated_at',
			'comic.code as comic_code',
			'comic_title.ulid',
			'language.lang as language_lang',
			'comic_title.content',
			'comic_title.is_synonym',
			'comic_title.is_latinized'
		]);

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_title.comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_title.comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		if (ct1.length == 1) {
			query = query.where('comic.code', '=', ct1[0]);
		} else {
			query = query.where('comic.code', 'in', ct1);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'comic_title.created_at';
					break;
				case 'updatedAt':
					name = 'comic_title.updated_at';
					break;
				case 'comicCode':
					name = 'comic.code';
					break;
				case 'languageLang':
					name = 'language.lang';
					break;
				case 'isSynonym':
					name = 'comic_title.is_synonym';
					break;
				case 'isLatinized':
					name = 'comic_title.is_latinized';
					break;
				case 'ulid':
				case 'content':
					name = 'comic_title.' + v.name;
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('comic_title.ulid');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			comicCode: result.comic_code,
			ulid: result.ulid,
			languageLang: result.language_lang,
			content: result.content,
			isSynonym: result.is_synonym,
			isLatinized: result.is_latinized
		};
	});
}

export async function selectComicTitleByKey(
	db: DB,
	comicCode: string,
	ulid: ULID
): Promise<model.ComicTitle | undefined> {
	const result = await db
		.selectFrom('comic_title')
		.innerJoin('comic', 'comic.id', 'comic_title.comic_id')
		.innerJoin('language', 'language.id', 'comic_title.language_id')
		.where((eb) => eb.and([eb('comic.code', '=', comicCode), eb('comic_title.ulid', '=', ulid)]))
		.select([
			'comic_title.id',
			'comic_title.created_at',
			'comic_title.updated_at',
			'comic.code as comic_code',
			'comic_title.ulid',
			'language.lang as language_lang',
			'comic_title.content',
			'comic_title.is_synonym',
			'comic_title.is_latinized'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		ulid: result.ulid,
		languageLang: result.language_lang,
		content: result.content,
		isSynonym: result.is_synonym,
		isLatinized: result.is_latinized
	};
}

export async function updateComicTitleByKey(
	db: DB,
	comicCode: string,
	ulid: ULID,
	data: model.SetComicTitle
): Promise<model.ComicTitle | undefined> {
	const result = await db
		.updateTable('comic_title')
		.innerJoin('comic', 'comic.id', 'comic_title.comic_id')
		.innerJoin('language', 'language.id', 'comic_title.language_id')
		.set(({ selectFrom }) => ({
			updated_at: new Date(),
			language_id: data.languageLang
				? selectFrom('language').select('id').where('lang', '=', data.languageLang)
				: undefined,
			content: data.content,
			is_synonym: data.isSynonym,
			is_latinized: data.isLatinized
		}))
		.where((eb) => eb.and([eb('comic.code', '=', comicCode), eb('comic_title.ulid', '=', ulid)]))
		.returning([
			'comic_title.id',
			'comic_title.created_at',
			'comic_title.updated_at',
			'comic.code as comic_code',
			'comic_title.ulid',
			'language.lang as language_lang',
			'comic_title.content',
			'comic_title.is_synonym',
			'comic_title.is_latinized'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		ulid: result.ulid,
		languageLang: result.language_lang,
		content: result.content,
		isSynonym: result.is_synonym,
		isLatinized: result.is_latinized
	};
}

export async function deleteComicTitleByKey(
	db: DB,
	comicCode: string,
	ulid: ULID
): Promise<boolean> {
	const result = await db
		.deleteFrom('comic_title')
		.innerJoin('comic', 'comic.id', 'comic_title.comic_id')
		.where((eb) => eb.and([eb('comic.code', '=', comicCode), eb('comic_title.ulid', '=', ulid)]))
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countComicTitle(db: DB, param: model.ParameterComicTitle): Promise<number> {
	let query = db.selectFrom('comic_title').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		const querx = query.innerJoin('comic', 'comic.id', 'comic_title.comic_id');

		if (ct1.length == 1) {
			query = querx.where('comic.code', '=', ct1[0]);
		} else {
			query = querx.where('comic.code', 'in', ct1);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

// Comic Cover

interface ComicCoverTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	comic_id: number;
	ulid: string;
	link_id: number;
	hint: string | null;
}

export async function insertComicCover(
	db: DB,
	data: model.NewComicCover
): Promise<model.ComicCover> {
	const result = await db
		.with('cte_comic', (db) => {
			return db.selectFrom('comic').select(['id', 'code']).where('code', '=', data.comicCode);
		})
		.with('cte_link', (db) => {
			return db
				.selectFrom('link')
				.innerJoin('website', 'website.id', 'link.website_id')
				.select(['link.id', 'website.host as website_host', 'link.relative_reference'])
				.where('website.host', '=', data.linkWebsiteHost)
				.where('link.relative_reference', '=', data.linkRelativeReference ?? '/');
		})
		.insertInto('comic_cover')
		.values(({ selectFrom }) => ({
			comic_id: selectFrom('cte_comic').select('id'),
			ulid: ulid(),
			link_id: selectFrom('cte_link').select('id'),
			hint: data.hint
		}))
		.returning(({ selectFrom }) => [
			'comic_cover.id',
			'comic_cover.created_at',
			'comic_cover.updated_at',
			selectFrom('cte_comic').select('code').as('comic_code'),
			'comic_cover.ulid',
			selectFrom('cte_link').select('website_host').as('link_website_host'),
			selectFrom('cte_link').select('relative_reference').as('link_relative_reference'),
			'comic_cover.hint'
		])
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code ?? '',
		ulid: result.ulid,
		linkWebsiteHost: result.link_website_host ?? '',
		linkRelativeReference: result.link_relative_reference ?? '',
		hint: result.hint
	};
}

export async function selectComicCover(
	db: DB,
	param: model.ParameterComicCover
): Promise<model.ComicCover[]> {
	let query = db
		.selectFrom('comic_cover')
		.innerJoin('comic', 'comic.id', 'comic_cover.comic_id')
		.innerJoin('link', 'link.id', 'comic_cover.link_id')
		.innerJoin('website as link_website', 'link_website.id', 'link.website_id')
		.select([
			'comic_cover.id',
			'comic_cover.created_at',
			'comic_cover.updated_at',
			'comic.code as comic_code',
			'comic_cover.ulid',
			'link_website.host as link_website_host',
			'link.relative_reference as link_relative_reference',
			'comic_cover.hint'
		]);

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_cover.comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_cover.comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		if (ct1.length == 1) {
			query = query.where('comic.code', '=', ct1[0]);
		} else {
			query = query.where('comic.code', 'in', ct1);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'comic_cover.created_at';
					break;
				case 'updatedAt':
					name = 'comic_cover.updated_at';
					break;
				case 'comicCode':
					name = 'comic.code';
					break;
				case 'linkWebsiteHost':
					name = 'link_website.host';
					break;
				case 'linkRelativeReference':
					name = 'link.relative_reference';
					break;
				case 'ulid':
				case 'hint':
					name = 'comic_cover.' + v.name;
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('comic_cover.ulid');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			comicCode: result.comic_code,
			ulid: result.ulid,
			linkWebsiteHost: result.link_website_host,
			linkRelativeReference: result.link_relative_reference,
			hint: result.hint
		};
	});
}

export async function selectComicCoverByKey(
	db: DB,
	comicCode: string,
	ulid: ULID
): Promise<model.ComicCover | undefined> {
	const result = await db
		.selectFrom('comic_cover')
		.innerJoin('comic', 'comic.id', 'comic_cover.comic_id')
		.innerJoin('link', 'link.id', 'comic_cover.link_id')
		.innerJoin('website as link_website', 'link_website.id', 'link.website_id')
		.where((eb) => eb.and([eb('comic.code', '=', comicCode), eb('comic_cover.ulid', '=', ulid)]))
		.select([
			'comic_cover.id',
			'comic_cover.created_at',
			'comic_cover.updated_at',
			'comic.code as comic_code',
			'comic_cover.ulid',
			'link_website.host as link_website_host',
			'link.relative_reference as link_relative_reference',
			'comic_cover.hint'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		ulid: result.ulid,
		linkWebsiteHost: result.link_website_host,
		linkRelativeReference: result.link_relative_reference,
		hint: result.hint
	};
}

export async function updateComicCoverByKey(
	db: DB,
	comicCode: string,
	ulid: ULID,
	data: model.SetComicCover
): Promise<model.ComicCover | undefined> {
	const result = await db
		.updateTable('comic_cover')
		.innerJoin('comic', 'comic.id', 'comic_cover.comic_id')
		.innerJoin('link', 'link.id', 'comic_cover.link_id')
		.innerJoin('website as link_website', 'link_website.id', 'link.website_id')
		.set(({ selectFrom }) => ({
			updated_at: new Date(),
			link_id: data.linkWebsiteHost
				? selectFrom('link')
						.innerJoin('website', 'website.id', 'link.website_id')
						.select('link.id')
						.where('website.host', '=', data.linkWebsiteHost)
						.where('link.relative_reference', '=', data.linkRelativeReference ?? '/')
				: undefined,
			hint: data.hint
		}))
		.where((eb) => eb.and([eb('comic.code', '=', comicCode), eb('comic_cover.ulid', '=', ulid)]))
		.returning([
			'comic_cover.id',
			'comic_cover.created_at',
			'comic_cover.updated_at',
			'comic.code as comic_code',
			'comic_cover.ulid',
			'link_website.host as link_website_host',
			'link.relative_reference as link_relative_reference',
			'comic_cover.hint'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		ulid: result.ulid,
		linkWebsiteHost: result.link_website_host,
		linkRelativeReference: result.link_relative_reference,
		hint: result.hint
	};
}

export async function deleteComicCoverByKey(
	db: DB,
	comicCode: string,
	ulid: ULID
): Promise<boolean> {
	const result = await db
		.deleteFrom('comic_cover')
		.innerJoin('comic', 'comic.id', 'comic_cover.comic_id')
		.where((eb) => eb.and([eb('comic.code', '=', comicCode), eb('comic_cover.ulid', '=', ulid)]))
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countComicCover(db: DB, param: model.ParameterComicCover): Promise<number> {
	let query = db.selectFrom('comic_cover').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		const querx = query.innerJoin('comic', 'comic.id', 'comic_cover.comic_id');

		if (ct1.length == 1) {
			query = querx.where('comic.code', '=', ct1[0]);
		} else {
			query = querx.where('comic.code', 'in', ct1);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

// Comic Synopsis

interface ComicSynopsisTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	comic_id: number;
	ulid: ULID;
	language_id: number;
	content: string;
	source: string | null;
}

export async function insertComicSynopsis(
	db: DB,
	data: model.NewComicSynopsis
): Promise<model.ComicSynopsis> {
	const result = await db
		.with('cte_comic', (db) => {
			return db.selectFrom('comic').select(['id', 'code']).where('code', '=', data.comicCode);
		})
		.with('cte_language', (db) => {
			return db.selectFrom('language').select(['id', 'lang']).where('lang', '=', data.languageLang);
		})
		.insertInto('comic_synopsis')
		.values(({ selectFrom }) => ({
			comic_id: selectFrom('cte_comic').select('id'),
			ulid: ulid(),
			language_id: selectFrom('cte_language').select('id'),
			content: data.content,
			source: data.source
		}))
		.returning(({ selectFrom }) => [
			'comic_synopsis.id',
			'comic_synopsis.created_at',
			'comic_synopsis.updated_at',
			selectFrom('cte_comic').select('code').as('comic_code'),
			'comic_synopsis.ulid',
			selectFrom('cte_language').select('lang').as('language_lang'),
			'comic_synopsis.content',
			'comic_synopsis.source'
		])
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code ?? '',
		ulid: result.ulid,
		languageLang: result.language_lang ?? '',
		content: result.content,
		source: result.source
	};
}

export async function selectComicSynopsis(
	db: DB,
	param: model.ParameterComicSynopsis
): Promise<model.ComicSynopsis[]> {
	let query = db
		.selectFrom('comic_synopsis')
		.innerJoin('comic', 'comic.id', 'comic_synopsis.comic_id')
		.innerJoin('language', 'language.id', 'comic_synopsis.language_id')
		.select([
			'comic_synopsis.id',
			'comic_synopsis.created_at',
			'comic_synopsis.updated_at',
			'comic.code as comic_code',
			'comic_synopsis.ulid',
			'language.lang as language_lang',
			'comic_synopsis.content',
			'comic_synopsis.source'
		]);

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_synopsis.comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_synopsis.comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		if (ct1.length == 1) {
			query = query.where('comic.code', '=', ct1[0]);
		} else {
			query = query.where('comic.code', 'in', ct1);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'comic_synopsis.created_at';
					break;
				case 'updatedAt':
					name = 'comic_synopsis.updated_at';
					break;
				case 'comicCode':
					name = 'comic.code';
					break;
				case 'languageLang':
					name = 'language.lang';
					break;
				case 'ulid':
				case 'source':
					name = 'comic_synopsis.' + v.name;
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('comic_synopsis.ulid');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			comicCode: result.comic_code,
			ulid: result.ulid,
			languageLang: result.language_lang,
			content: result.content,
			source: result.source
		};
	});
}

export async function selectComicSynopsisByKey(
	db: DB,
	comicCode: string,
	ulid: ULID
): Promise<model.ComicSynopsis | undefined> {
	const result = await db
		.selectFrom('comic_synopsis')
		.innerJoin('comic', 'comic.id', 'comic_synopsis.comic_id')
		.innerJoin('language', 'language.id', 'comic_synopsis.language_id')
		.where((eb) => eb.and([eb('comic.code', '=', comicCode), eb('comic_synopsis.ulid', '=', ulid)]))
		.select([
			'comic_synopsis.id',
			'comic_synopsis.created_at',
			'comic_synopsis.updated_at',
			'comic.code as comic_code',
			'comic_synopsis.ulid',
			'language.lang as language_lang',
			'comic_synopsis.content',
			'comic_synopsis.source'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		ulid: result.ulid,
		languageLang: result.language_lang,
		content: result.content,
		source: result.source
	};
}

export async function updateComicSynopsisByKey(
	db: DB,
	comicCode: string,
	ulid: ULID,
	data: model.SetComicSynopsis
): Promise<model.ComicSynopsis | undefined> {
	const result = await db
		.updateTable('comic_synopsis')
		.innerJoin('comic', 'comic.id', 'comic_synopsis.comic_id')
		.innerJoin('language', 'language.id', 'comic_synopsis.language_id')
		.set(({ selectFrom }) => ({
			updated_at: new Date(),
			language_id: data.languageLang
				? selectFrom('language').select('id').where('lang', '=', data.languageLang)
				: undefined,
			content: data.content,
			source: data.source
		}))
		.where((eb) => eb.and([eb('comic.code', '=', comicCode), eb('comic_synopsis.ulid', '=', ulid)]))
		.returning([
			'comic_synopsis.id',
			'comic_synopsis.created_at',
			'comic_synopsis.updated_at',
			'comic.code as comic_code',
			'comic_synopsis.ulid',
			'language.lang as language_lang',
			'comic_synopsis.content',
			'comic_synopsis.source'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		ulid: result.ulid,
		languageLang: result.language_lang,
		content: result.content,
		source: result.source
	};
}

export async function deleteComicSynopsisByKey(
	db: DB,
	comicCode: string,
	ulid: ULID
): Promise<boolean> {
	const result = await db
		.deleteFrom('comic_synopsis')
		.innerJoin('comic', 'comic.id', 'comic_synopsis.comic_id')
		.where((eb) => eb.and([eb('comic.code', '=', comicCode), eb('comic_synopsis.ulid', '=', ulid)]))
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countComicSynopsis(
	db: DB,
	param: model.ParameterComicSynopsis
): Promise<number> {
	let query = db.selectFrom('comic_synopsis').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		const querx = query.innerJoin('comic', 'comic.id', 'comic_synopsis.comic_id');

		if (ct1.length == 1) {
			query = querx.where('comic.code', '=', ct1[0]);
		} else {
			query = querx.where('comic.code', 'in', ct1);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

// Comic Character

interface ComicCharacterTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	comic_id: number;
	character_id: number;
	is_main: boolean | null;
}

export async function insertComicCharacter(
	db: DB,
	data: model.NewComicCharacter
): Promise<model.ComicCharacter> {
	const result = await db
		.with('cte_comic', (db) => {
			return db.selectFrom('comic').select(['id', 'code']).where('code', '=', data.comicCode);
		})
		.with('cte_character', (db) => {
			return db
				.selectFrom('character')
				.select(['id', 'code'])
				.where('code', '=', data.characterCode);
		})
		.insertInto('comic_character')
		.values(({ selectFrom }) => ({
			comic_id: selectFrom('cte_comic').select('id'),
			character_id: selectFrom('cte_character').select('id'),
			is_main: data.isMain
		}))
		.returning(({ selectFrom }) => [
			'comic_character.id',
			'comic_character.created_at',
			'comic_character.updated_at',
			selectFrom('cte_comic').select('code').as('comic_code'),
			selectFrom('cte_character').select('code').as('character_code'),
			'comic_character.is_main'
		])
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code ?? '',
		characterCode: result.character_code ?? '',
		isMain: result.is_main
	};
}

export async function selectComicCharacter(
	db: DB,
	param: model.ParameterComicCharacter
): Promise<model.ComicCharacter[]> {
	let query = db
		.selectFrom('comic_character')
		.innerJoin('comic', 'comic.id', 'comic_character.comic_id')
		.innerJoin('character', 'character.id', 'comic_character.character_id')
		.select([
			'comic_character.id',
			'comic_character.created_at',
			'comic_character.updated_at',
			'comic.code as comic_code',
			'character.code as character_code',
			'comic_character.is_main'
		]);

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_character.comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_character.comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		if (ct1.length == 1) {
			query = query.where('comic.code', '=', ct1[0]);
		} else {
			query = query.where('comic.code', 'in', ct1);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'comic_character.created_at';
					break;
				case 'updatedAt':
					name = 'comic_character.updated_at';
					break;
				case 'comicCode':
					name = 'comic.code';
					break;
				case 'characterCode':
					name = 'character.code';
					break;
				case 'isMain':
					name = 'comic_character.is_main';
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('character.code');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			comicCode: result.comic_code,
			characterCode: result.character_code,
			isMain: result.is_main
		};
	});
}

export async function selectComicCharacterByKey(
	db: DB,
	comicCode: string,
	characterCode: string
): Promise<model.ComicCharacter | undefined> {
	const result = await db
		.selectFrom('comic_character')
		.innerJoin('comic', 'comic.id', 'comic_character.comic_id')
		.innerJoin('character', 'character.id', 'comic_character.character_id')
		.where((eb) =>
			eb.and([eb('comic.code', '=', comicCode), eb('character.code', '=', characterCode)])
		)
		.select([
			'comic_character.id',
			'comic_character.created_at',
			'comic_character.updated_at',
			'comic.code as comic_code',
			'character.code as character_code',
			'comic_character.is_main'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		characterCode: result.character_code,
		isMain: result.is_main
	};
}

export async function updateComicCharacterByKey(
	db: DB,
	comicCode: string,
	characterCode: string,
	data: model.SetComicCharacter
): Promise<model.ComicCharacter | undefined> {
	const result = await db
		.updateTable('comic_character')
		.innerJoin('comic', 'comic.id', 'comic_character.comic_id')
		.innerJoin('character', 'character.id', 'comic_character.character_id')
		.set(({ selectFrom }) => ({
			updated_at: new Date(),
			character_id: data.characterCode
				? selectFrom('character').select('id').where('code', '=', data.characterCode)
				: undefined,
			is_main: data.isMain
		}))
		.where((eb) =>
			eb.and([eb('comic.code', '=', comicCode), eb('character.code', '=', characterCode)])
		)
		.returning([
			'comic_character.id',
			'comic_character.created_at',
			'comic_character.updated_at',
			'comic.code as comic_code',
			'character.code as character_code',
			'comic_character.is_main'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		characterCode: result.character_code,
		isMain: result.is_main
	};
}

export async function deleteComicCharacterByKey(
	db: DB,
	comicCode: string,
	characterCode: string
): Promise<boolean> {
	const result = await db
		.deleteFrom('comic_character')
		.innerJoin('comic', 'comic.id', 'comic_character.comic_id')
		.innerJoin('character', 'character.id', 'comic_character.character_id')
		.where((eb) =>
			eb.and([eb('comic.code', '=', comicCode), eb('character.code', '=', characterCode)])
		)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countComicCharacter(
	db: DB,
	param: model.ParameterComicCharacter
): Promise<number> {
	let query = db.selectFrom('comic_character').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		const querx = query.innerJoin('comic', 'comic.id', 'comic_character.comic_id');

		if (ct1.length == 1) {
			query = querx.where('comic.code', '=', ct1[0]);
		} else {
			query = querx.where('comic.code', 'in', ct1);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

// Comic Author

interface ComicAuthorTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	comic_id: number;
	position_id: number;
	person_id: number;
}

export async function insertComicAuthor(
	db: DB,
	data: model.NewComicAuthor
): Promise<model.ComicAuthor> {
	const result = await db
		.with('cte_comic', (db) => {
			return db.selectFrom('comic').select(['id', 'code']).where('code', '=', data.comicCode);
		})
		.with('cte_position', (db) => {
			return db
				.selectFrom('comic_author_position')
				.select(['id', 'code'])
				.where('code', '=', data.positionCode);
		})
		.with('cte_person', (db) => {
			return db.selectFrom('person').select(['id', 'code']).where('code', '=', data.personCode);
		})
		.insertInto('comic_author')
		.values(({ selectFrom }) => ({
			comic_id: selectFrom('cte_comic').select('id'),
			position_id: selectFrom('cte_position').select('id'),
			person_id: selectFrom('cte_person').select('id')
		}))
		.returning(({ selectFrom }) => [
			'comic_author.id',
			'comic_author.created_at',
			'comic_author.updated_at',
			selectFrom('cte_comic').select('code').as('comic_code'),
			selectFrom('cte_position').select('code').as('position_code'),
			selectFrom('cte_person').select('code').as('person_code')
		])
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code ?? '',
		positionCode: result.position_code ?? '',
		personCode: result.person_code ?? ''
	};
}

export async function selectComicAuthor(
	db: DB,
	param: model.ParameterComicAuthor
): Promise<model.ComicAuthor[]> {
	let query = db
		.selectFrom('comic_author')
		.innerJoin('comic', 'comic.id', 'comic_author.comic_id')
		.innerJoin('comic_author_position', 'comic_author_position.id', 'comic_author.position_id')
		.innerJoin('person', 'person.id', 'comic_author.person_id')
		.select([
			'comic_author.id',
			'comic_author.created_at',
			'comic_author.updated_at',
			'comic.code as comic_code',
			'comic_author_position.code as position_code',
			'person.code as person_code'
		]);

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_author.comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_author.comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		if (ct1.length == 1) {
			query = query.where('comic.code', '=', ct1[0]);
		} else {
			query = query.where('comic.code', 'in', ct1);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'comic_author.created_at';
					break;
				case 'updatedAt':
					name = 'comic_author.updated_at';
					break;
				case 'comicCode':
					name = 'comic.code';
					break;
				case 'positionCode':
					name = 'comic_author_position.code';
					break;
				case 'personCode':
					name = 'person.code';
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('comic_author_position.code');
		query = query.orderBy('person.code');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			comicCode: result.comic_code,
			positionCode: result.position_code,
			personCode: result.person_code
		};
	});
}

export async function selectComicAuthorByKey(
	db: DB,
	comicCode: string,
	positionCode: string,
	personCode: string
): Promise<model.ComicAuthor | undefined> {
	const result = await db
		.selectFrom('comic_author')
		.innerJoin('comic', 'comic.id', 'comic_author.comic_id')
		.innerJoin('comic_author_position', 'comic_author_position.id', 'comic_author.position_id')
		.innerJoin('person', 'person.id', 'comic_author.person_id')
		.where((eb) =>
			eb.and([
				eb('comic.code', '=', comicCode),
				eb('comic_author_position.code', '=', positionCode),
				eb('person.code', '=', personCode)
			])
		)
		.select([
			'comic_author.id',
			'comic_author.created_at',
			'comic_author.updated_at',
			'comic.code as comic_code',
			'comic_author_position.code as position_code',
			'person.code as person_code'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		positionCode: result.position_code,
		personCode: result.person_code
	};
}

export async function updateComicAuthorByKey(
	db: DB,
	comicCode: string,
	positionCode: string,
	personCode: string,
	data: model.SetComicAuthor
): Promise<model.ComicAuthor | undefined> {
	const result = await db
		.updateTable('comic_author')
		.innerJoin('comic', 'comic.id', 'comic_author.comic_id')
		.innerJoin('comic_author_position', 'comic_author_position.id', 'comic_author.position_id')
		.innerJoin('person', 'person.id', 'comic_author.person_id')
		.set(({ selectFrom }) => ({
			updated_at: new Date(),
			position_id: data.positionCode
				? selectFrom('comic_author_position').select('id').where('code', '=', data.positionCode)
				: undefined,
			person_id: data.personCode
				? selectFrom('person').select('id').where('code', '=', data.personCode)
				: undefined
		}))
		.where((eb) =>
			eb.and([
				eb('comic.code', '=', comicCode),
				eb('comic_author_position.code', '=', positionCode),
				eb('person.code', '=', personCode)
			])
		)
		.returning([
			'comic_author.id',
			'comic_author.created_at',
			'comic_author.updated_at',
			'comic.code as comic_code',
			'comic_author_position.code as position_code',
			'person.code as person_code'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		positionCode: result.position_code,
		personCode: result.person_code
	};
}

export async function deleteComicAuthorByKey(
	db: DB,
	comicCode: string,
	positionCode: string,
	personCode: string
): Promise<boolean> {
	const result = await db
		.deleteFrom('comic_author')
		.innerJoin('comic', 'comic.id', 'comic_author.comic_id')
		.innerJoin('comic_author_position', 'comic_author_position.id', 'comic_author.position_id')
		.innerJoin('person', 'person.id', 'comic_author.person_id')
		.where((eb) =>
			eb.and([
				eb('comic.code', '=', comicCode),
				eb('comic_author_position.code', '=', positionCode),
				eb('person.code', '=', personCode)
			])
		)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countComicAuthor(db: DB, param: model.ParameterComicAuthor): Promise<number> {
	let query = db.selectFrom('comic_author').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		const querx = query.innerJoin('comic', 'comic.id', 'comic_author.comic_id');

		if (ct1.length == 1) {
			query = querx.where('comic.code', '=', ct1[0]);
		} else {
			query = querx.where('comic.code', 'in', ct1);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

// Comic Serialization

interface ComicSerializationTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	comic_id: number;
	magazine_id: number;
}

export async function insertComicSerialization(
	db: DB,
	data: model.NewComicSerialization
): Promise<model.ComicSerialization> {
	const result = await db
		.with('cte_comic', (db) => {
			return db.selectFrom('comic').select(['id', 'code']).where('code', '=', data.comicCode);
		})
		.with('cte_magazine', (db) => {
			return db.selectFrom('magazine').select(['id', 'code']).where('code', '=', data.magazineCode);
		})
		.insertInto('comic_serialization')
		.values(({ selectFrom }) => ({
			comic_id: selectFrom('cte_comic').select('id'),
			magazine_id: selectFrom('cte_magazine').select('id')
		}))
		.returning(({ selectFrom }) => [
			'comic_serialization.id',
			'comic_serialization.created_at',
			'comic_serialization.updated_at',
			selectFrom('cte_comic').select('code').as('comic_code'),
			selectFrom('cte_magazine').select('code').as('magazine_code')
		])
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code ?? '',
		magazineCode: result.magazine_code ?? ''
	};
}

export async function selectComicSerialization(
	db: DB,
	param: model.ParameterComicSerialization
): Promise<model.ComicSerialization[]> {
	let query = db
		.selectFrom('comic_serialization')
		.innerJoin('comic', 'comic.id', 'comic_serialization.comic_id')
		.innerJoin('magazine', 'magazine.id', 'comic_serialization.magazine_id')
		.select([
			'comic_serialization.id',
			'comic_serialization.created_at',
			'comic_serialization.updated_at',
			'comic.code as comic_code',
			'magazine.code as magazine_code'
		]);

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_serialization.comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_serialization.comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		if (ct1.length == 1) {
			query = query.where('comic.code', '=', ct1[0]);
		} else {
			query = query.where('comic.code', 'in', ct1);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'comic_serialization.created_at';
					break;
				case 'updatedAt':
					name = 'comic_serialization.updated_at';
					break;
				case 'comicCode':
					name = 'comic.code';
					break;
				case 'magazineCode':
					name = 'magazine.code';
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('magazine.code');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			comicCode: result.magazine_code,
			magazineCode: result.magazine_code
		};
	});
}

export async function selectComicSerializationByKey(
	db: DB,
	comicCode: string,
	magazineCode: string
): Promise<model.ComicSerialization | undefined> {
	const result = await db
		.selectFrom('comic_serialization')
		.innerJoin('comic', 'comic.id', 'comic_serialization.comic_id')
		.innerJoin('magazine', 'magazine.id', 'comic_serialization.magazine_id')
		.where((eb) =>
			eb.and([eb('comic.code', '=', comicCode), eb('magazine.code', '=', magazineCode)])
		)
		.select([
			'comic_serialization.id',
			'comic_serialization.created_at',
			'comic_serialization.updated_at',
			'comic.code as comic_code',
			'magazine.code as magazine_code'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		magazineCode: result.magazine_code
	};
}

export async function updateComicSerializationByKey(
	db: DB,
	comicCode: string,
	magazineCode: string,
	data: model.SetComicSerialization
): Promise<model.ComicSerialization | undefined> {
	const result = await db
		.updateTable('comic_serialization')
		.innerJoin('comic', 'comic.id', 'comic_serialization.comic_id')
		.innerJoin('magazine', 'magazine.id', 'comic_serialization.magazine_id')
		.set(({ selectFrom }) => ({
			updated_at: new Date(),
			magazine_id: data.magazineCode
				? selectFrom('magazine').select('id').where('code', '=', data.magazineCode)
				: undefined
		}))
		.where((eb) =>
			eb.and([eb('comic.code', '=', comicCode), eb('magazine.code', '=', magazineCode)])
		)
		.returning([
			'comic_serialization.id',
			'comic_serialization.created_at',
			'comic_serialization.updated_at',
			'comic.code as comic_code',
			'magazine.code as magazine_code'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		magazineCode: result.magazine_code
	};
}

export async function deleteComicSerializationByKey(
	db: DB,
	comicCode: string,
	magazineCode: string
): Promise<boolean> {
	const result = await db
		.deleteFrom('comic_serialization')
		.innerJoin('comic', 'comic.id', 'comic_serialization.comic_id')
		.innerJoin('magazine', 'magazine.id', 'comic_serialization.magazine_id')
		.where((eb) =>
			eb.and([eb('comic.code', '=', comicCode), eb('magazine.code', '=', magazineCode)])
		)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countComicSerialization(
	db: DB,
	param: model.ParameterComicSerialization
): Promise<number> {
	let query = db
		.selectFrom('comic_serialization')
		.select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		const querx = query.innerJoin('comic', 'comic.id', 'comic_serialization.comic_id');

		if (ct1.length == 1) {
			query = querx.where('comic.code', '=', ct1[0]);
		} else {
			query = querx.where('comic.code', 'in', ct1);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

// Comic External

interface ComicExternalTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	comic_id: number;
	link_id: number;
	is_official: boolean | null;
	is_community: boolean | null;
}

export async function insertComicExternal(
	db: DB,
	data: model.NewComicExternal
): Promise<model.ComicExternal> {
	const result = await db
		.with('cte_comic', (db) => {
			return db.selectFrom('comic').select(['id', 'code']).where('code', '=', data.comicCode);
		})
		.with('cte_link', (db) => {
			return db
				.selectFrom('link')
				.innerJoin('website', 'website.id', 'link.website_id')
				.select(['link.id', 'website.host as website_host', 'link.relative_reference'])
				.where('website.host', '=', data.linkWebsiteHost)
				.where('link.relative_reference', '=', data.linkRelativeReference ?? '/');
		})
		.insertInto('comic_external')
		.values(({ selectFrom }) => ({
			comic_id: selectFrom('cte_comic').select('id'),
			link_id: selectFrom('cte_link').select('id')
		}))
		.returning(({ selectFrom }) => [
			'comic_external.id',
			'comic_external.created_at',
			'comic_external.updated_at',
			selectFrom('cte_comic').select('code').as('comic_code'),
			selectFrom('cte_link').select('website_host').as('link_website_host'),
			selectFrom('cte_link').select('relative_reference').as('link_relative_reference'),
			'comic_external.is_official',
			'comic_external.is_community'
		])
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code ?? '',
		linkWebsiteHost: result.link_website_host ?? '',
		linkRelativeReference: result.link_relative_reference ?? '',
		isOfficial: result.is_official,
		isCommunity: result.is_community
	};
}

export async function selectComicExternal(
	db: DB,
	param: model.ParameterComicExternal
): Promise<model.ComicExternal[]> {
	let query = db
		.selectFrom('comic_external')
		.innerJoin('comic', 'comic.id', 'comic_external.comic_id')
		.innerJoin('link', 'link.id', 'comic_external.link_id')
		.innerJoin('website as link_website', 'link_website.id', 'link.website_id')
		.select([
			'comic_external.id',
			'comic_external.created_at',
			'comic_external.updated_at',
			'comic.code as comic_code',
			'link_website.host as link_website_host',
			'link.relative_reference as link_relative_reference',
			'comic_external.is_official',
			'comic_external.is_community'
		]);

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_external.comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_external.comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		if (ct1.length == 1) {
			query = query.where('comic.code', '=', ct1[0]);
		} else {
			query = query.where('comic.code', 'in', ct1);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'comic_external.created_at';
					break;
				case 'updatedAt':
					name = 'comic_external.updated_at';
					break;
				case 'comicCode':
					name = 'comic.code';
					break;
				case 'linkWebsiteHost':
					name = 'link_website.host';
					break;
				case 'linkRelativeReference':
					name = 'link.relative_reference';
					break;
				case 'isOfficial':
					name = 'comic_external.is_official';
					break;
				case 'isCommunity':
					name = 'comic_external.is_community';
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('link_website.host');
		query = query.orderBy('link.relative_reference');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			comicCode: result.comic_code,
			linkWebsiteHost: result.link_website_host,
			linkRelativeReference: result.link_relative_reference,
			isOfficial: result.is_official,
			isCommunity: result.is_community
		};
	});
}

export async function selectComicExternalByKey(
	db: DB,
	comicCode: string,
	linkWebsiteHost: string,
	linkRelativeReference: string
): Promise<model.ComicExternal | undefined> {
	const result = await db
		.selectFrom('comic_external')
		.innerJoin('comic', 'comic.id', 'comic_external.comic_id')
		.innerJoin('link', 'link.id', 'comic_external.link_id')
		.innerJoin('website as link_website', 'link_website.id', 'link.website_id')
		.where((eb) =>
			eb.and([
				eb('comic.code', '=', comicCode),
				eb('link_website.host', '=', linkWebsiteHost),
				eb('link.relative_reference', '=', linkRelativeReference)
			])
		)
		.select([
			'comic_external.id',
			'comic_external.created_at',
			'comic_external.updated_at',
			'comic.code as comic_code',
			'link_website.host as link_website_host',
			'link.relative_reference as link_relative_reference',
			'comic_external.is_official',
			'comic_external.is_community'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		linkWebsiteHost: result.link_website_host,
		linkRelativeReference: result.link_relative_reference,
		isOfficial: result.is_official,
		isCommunity: result.is_community
	};
}

export async function updateComicExternalByKey(
	db: DB,
	comicCode: string,
	linkWebsiteHost: string,
	linkRelativeReference: string,
	data: model.SetComicExternal
): Promise<model.ComicExternal | undefined> {
	const result = await db
		.updateTable('comic_external')
		.innerJoin('comic', 'comic.id', 'comic_external.comic_id')
		.innerJoin('link', 'link.id', 'comic_external.link_id')
		.innerJoin('website as link_website', 'link_website.id', 'link.website_id')
		.set(({ selectFrom }) => ({
			updated_at: new Date(),
			link_id: data.linkWebsiteHost
				? selectFrom('link')
						.innerJoin('website', 'website.id', 'link.website_id')
						.select('link.id')
						.where('website.host', '=', data.linkWebsiteHost)
						.where('link.relative_reference', '=', data.linkRelativeReference ?? '/')
				: undefined,
			is_official: data.isOfficial,
			is_community: data.isCommunity
		}))
		.where((eb) =>
			eb.and([
				eb('comic.code', '=', comicCode),
				eb('link_website.host', '=', linkWebsiteHost),
				eb('link.relative_reference', '=', linkRelativeReference)
			])
		)
		.returning([
			'comic_external.id',
			'comic_external.created_at',
			'comic_external.updated_at',
			'comic.code as comic_code',
			'link_website.host as link_website_host',
			'link.relative_reference as link_relative_reference',
			'comic_external.is_official',
			'comic_external.is_community'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		linkWebsiteHost: result.link_website_host,
		linkRelativeReference: result.link_relative_reference,
		isOfficial: result.is_official,
		isCommunity: result.is_community
	};
}

export async function deleteComicExternalByKey(
	db: DB,
	comicCode: string,
	linkWebsiteHost: string,
	linkRelativeReference: string
): Promise<boolean> {
	const result = await db
		.deleteFrom('comic_external')
		.innerJoin('comic', 'comic.id', 'comic_external.comic_id')
		.innerJoin('link', 'link.id', 'comic_external.link_id')
		.innerJoin('website as link_website', 'link_website.id', 'link.website_id')
		.where((eb) =>
			eb.and([
				eb('comic.code', '=', comicCode),
				eb('link_website.host', '=', linkWebsiteHost),
				eb('link.relative_reference', '=', linkRelativeReference)
			])
		)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countComicExternal(
	db: DB,
	param: model.ParameterComicExternal
): Promise<number> {
	let query = db.selectFrom('comic_external').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		const querx = query.innerJoin('comic', 'comic.id', 'comic_external.comic_id');

		if (ct1.length == 1) {
			query = querx.where('comic.code', '=', ct1[0]);
		} else {
			query = querx.where('comic.code', 'in', ct1);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

// Comic Chapter

interface ComicChapterTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	comic_id: number;
	number: number;
	version: string | null;
	released_at: Date | null;
	volume_id: number | null;
}

export async function insertComicChapter(
	db: DB,
	data: model.NewComicChapter
): Promise<model.ComicChapter> {
	const result = await db
		.with('cte_comic', (db) => {
			return db.selectFrom('comic').select(['id', 'code']).where('code', '=', data.comicCode);
		})
		.with('cte_comic_volume', (db) => {
			return db
				.selectFrom('comic_volume')
				.where('comic_volume.number', '=', data.volumeNumber ?? NaN);
		})
		.insertInto('comic_chapter')
		.values(({ selectFrom }) => ({
			comic_id: selectFrom('cte_comic').select('id'),
			number: data.number,
			version: data.version,
			released_at: data.releasedAt,
			volume_id: selectFrom('cte_comic_volume').select('id')
		}))
		.returning(({ selectFrom }) => [
			'comic_chapter.id',
			'comic_chapter.created_at',
			'comic_chapter.updated_at',
			selectFrom('cte_comic').select('code').as('comic_code'),
			'comic_chapter.number',
			'comic_chapter.version',
			'comic_chapter.released_at',
			selectFrom('cte_comic_volume').select('number').as('volume_number')
		])
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code ?? '',
		number: result.number,
		version: result.version,
		releasedAt: result.released_at,
		volumeNumber: result.volume_number,
		titleCount: 0
	};
}

export async function selectComicChapter(
	db: DB,
	param: model.ParameterComicChapter
): Promise<model.ComicChapter[]> {
	let query = db
		.selectFrom('comic_chapter')
		.innerJoin('comic', 'comic.id', 'comic_chapter.comic_id')
		.innerJoin('comic_volume', 'comic_volume.id', 'comic_chapter.volume_id')
		.select([
			'comic_chapter.id',
			'comic_chapter.created_at',
			'comic_chapter.updated_at',
			'comic.code as comic_code',
			'comic_chapter.number',
			'comic_chapter.version',
			'comic_chapter.released_at',
			'comic_volume.number as volume_number'
		]);

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_chapter.comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_chapter.comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		if (ct1.length == 1) {
			query = query.where('comic.code', '=', ct1[0]);
		} else {
			query = query.where('comic.code', 'in', ct1);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'comic_chapter.created_at';
					break;
				case 'updatedAt':
					name = 'comic_chapter.updated_at';
					break;
				case 'comicCode':
					name = 'comic.code';
					break;
				case 'releasedAt':
					name = 'comic_chapter.released_at';
					break;
				case 'volumeNumber':
					name = 'comic_volume.number';
					break;
				case 'number':
				case 'version':
					name = 'comic_chapter.' + v.name;
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('comic_chapter.number');
		query = query.orderBy('comic_chapter.version');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			number: result.number,
			version: result.version,
			releasedAt: result.released_at,
			volumeNumber: result.volume_number,
			titleCount: -1
		};
	});
}

export async function selectComicChapterByKey(
	db: DB,
	comicCode: string,
	number: number,
	version: string | null
): Promise<model.ComicChapter | undefined> {
	const result = await db
		.selectFrom('comic_chapter')
		.innerJoin('comic', 'comic.id', 'comic_chapter.comic_id')
		.innerJoin('comic_volume', 'comic_volume.id', 'comic_chapter.volume_id')
		.where((eb) =>
			eb.and([
				eb('comic.code', '=', comicCode),
				eb('comic_chapter.number', '=', number),
				eb('comic_chapter.version', '=', version)
			])
		)
		.select([
			'comic_chapter.id',
			'comic_chapter.created_at',
			'comic_chapter.updated_at',
			'comic.code as comic_code',
			'comic_chapter.number',
			'comic_chapter.version',
			'comic_chapter.released_at',
			'comic_volume.number as volume_number'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		number: result.number,
		version: result.version,
		releasedAt: result.released_at,
		volumeNumber: result.volume_number,
		titleCount: -1
	};
}

export async function updateComicChapterByKey(
	db: DB,
	comicCode: string,
	number: number,
	version: string | null,
	data: model.SetComicChapter
): Promise<model.ComicChapter | undefined> {
	const result = await db
		.updateTable('comic_chapter')
		.innerJoin('comic', 'comic.id', 'comic_chapter.comic_id')
		.innerJoin('comic_volume', 'comic_volume.id', 'comic_chapter.volume_id')
		.set(({ selectFrom }) => ({
			updated_at: new Date(),
			number: data.number,
			version: data.version,
			released_at: data.releasedAt,
			volume_id: data.volumeNumber
				? selectFrom('comic_volume')
						.select('id')
						.where('comic_volume.number', '=', data.volumeNumber ?? '')
				: undefined
		}))
		.where((eb) =>
			eb.and([
				eb('comic.code', '=', comicCode),
				eb('comic_chapter.number', '=', number),
				eb('comic_chapter.version', '=', version)
			])
		)
		.returning([
			'comic_chapter.id',
			'comic_chapter.created_at',
			'comic_chapter.updated_at',
			'comic.code as comic_code',
			'comic_chapter.number',
			'comic_chapter.version',
			'comic_chapter.released_at',
			'comic_volume.number as volume_number'
		])
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		comicCode: result.comic_code,
		number: result.number,
		version: result.version,
		releasedAt: result.released_at,
		volumeNumber: result.volume_number,
		titleCount: -1
	};
}

export async function deleteComicChapterByKey(
	db: DB,
	comicCode: string,
	number: number,
	version: string | null
): Promise<boolean> {
	const result = await db
		.deleteFrom('comic_chapter')
		.innerJoin('comic', 'comic.id', 'comic_chapter.comic_id')
		.where((eb) =>
			eb.and([
				eb('comic.code', '=', comicCode),
				eb('comic_chapter.number', '=', number),
				eb('comic_chapter.version', '=', version)
			])
		)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countComicChapter(
	db: DB,
	param: model.ParameterComicChapter
): Promise<number> {
	let query = db.selectFrom('comic_chapter').select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaComicIDs ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('comic_id', '=', ct0[0]);
		} else {
			query = query.where('comic_id', 'in', ct0);
		}
	}

	const ct1 = param.criteriaComicCodes ?? [];
	if (ct1.length > 0) {
		const querx = query.innerJoin('comic', 'comic.id', 'comic_chapter.comic_id');

		if (ct1.length == 1) {
			query = querx.where('comic.code', '=', ct1[0]);
		} else {
			query = querx.where('comic.code', 'in', ct1);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

// Comic Chapter Title

interface ComicChapterTitleTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	comic_id: number;
	chapter_id: number;
	ulid: string;
	language_id: number;
	content: string;
	is_synonym: boolean | null;
	is_latinized: boolean | null;
}

export async function selectComicChapterTitle(
	param: model.ParameterComicChapterTitle
): Promise<model.ComicChapterTitle[]> {
	const result = await database
		.selectFrom('comic_chapter_title')
		.innerJoin('language', 'language.id', 'language_id')
		.select([
			'comic_chapter_title.id',
			'comic_chapter_title.created_at',
			'comic_chapter_title.updated_at',
			'comic_chapter_title.ulid',
			'language.lang as language_lang',
			'comic_chapter_title.content',
			'comic_chapter_title.is_synonym',
			'comic_chapter_title.is_latinized'
		])
		.$if((param.comicID ?? 0) > 0, (qb) => {
			return qb.where('comic_id', '=', param.comicID ?? 0);
		})
		.$if((param.comicCode ?? '') != '', (qb) => {
			return qb
				.innerJoin('comic', 'comic.id', 'comic_chapter_title.comic_id')
				.where('comic.code', '=', param.comicCode ?? '');
		})
		.$if((param.chapterID ?? 0) > 0, (qb) => {
			return qb.where('chapter_id', '=', param.chapterID ?? 0);
		})
		.$if((param.ulid ?? '') != '', (qb) => {
			return qb.where('ulid', '=', param.ulid ?? '');
		})
		.execute()
		.catch(catchExecption);

	return result.map((result) => {
		return {
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			ulid: result.ulid,
			languageLang: result.language_lang,
			content: result.content,
			isSynonym: result.is_synonym,
			isLatinized: result.is_latinized
		};
	});
}

export async function countComicChapterTitle(
	param: model.ParameterComicChapterTitle
): Promise<number> {
	const { count } = await database
		.selectFrom('comic_chapter_title')
		.select((eb) => eb.fn.countAll<number>().as('count'))
		.$if((param.comicID ?? 0) > 0, (qb) => {
			return qb.where('comic_id', '=', param.comicID ?? 0);
		})
		.$if((param.comicCode ?? '') != '', (qb) => {
			return qb
				.innerJoin('comic', 'comic.id', 'comic_chapter_title.comic_id')
				.where('comic.code', '=', param.comicCode ?? '');
		})
		.$if((param.chapterID ?? 0) > 0, (qb) => {
			return qb.where('chapter_id', '=', param.chapterID ?? 0);
		})
		.$if((param.ulid ?? '') != '', (qb) => {
			return qb.where('ulid', '=', param.ulid ?? '');
		})
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return count;
}

// Comic Volume

interface ComicVolumeTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	comic_id: number;
	number: number;
	released_at: Date | null;
}

export async function selectComicVolume(
	param: model.ParameterComicVolume
): Promise<model.ComicVolume[]> {
	const result = await database
		.selectFrom('comic_volume')
		.selectAll()
		.$if((param.comicID ?? 0) > 0, (qb) => {
			return qb.where('comic_id', '=', param.comicID ?? 0);
		})
		.$if((param.comicCode ?? '') != '', (qb) => {
			return qb
				.innerJoin('comic', 'comic.id', 'comic_id')
				.where('comic.code', '=', param.comicCode ?? '');
		})
		.execute()
		.catch(catchExecption);

	return Promise.all(
		result.map(async (result) => {
			return {
				createdAt: result.created_at,
				updatedAt: result.updated_at,
				number: result.number,
				releasedAt: result.released_at,
				titleCount: await countComicVolumeTitle({
					comicID: param.comicID,
					comicCode: param.comicCode,
					volumeID: result.id
				}),
				coverCount: await countComicVolumeCover({
					comicID: param.comicID,
					comicCode: param.comicCode,
					volumeID: result.id
				}),
				chapterCount: -1
			};
		})
	);
}

export async function countComicVolume(param: model.ParameterComicVolume): Promise<number> {
	const { count } = await database
		.selectFrom('comic_volume')
		.select((eb) => eb.fn.countAll<number>().as('count'))
		.$if((param.comicID ?? 0) > 0, (qb) => {
			return qb.where('comic_id', '=', param.comicID ?? 0);
		})
		.$if((param.comicCode ?? '') != '', (qb) => {
			return qb
				.innerJoin('comic', 'comic.id', 'comic_volume.comic_id')
				.where('comic.code', '=', param.comicCode ?? '');
		})
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return count;
}

// Comic Volume Title

interface ComicVolumeTitleTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	comic_id: number;
	volume_id: number;
	ulid: string;
	language_id: number;
	content: string;
	is_synonym: boolean | null;
	is_latinized: boolean | null;
}

export async function selectComicVolumeTitle(
	param: model.ParameterComicVolumeTitle
): Promise<model.ComicVolumeTitle[]> {
	const result = await database
		.selectFrom('comic_volume_title')
		.innerJoin('language', 'language.id', 'language_id')
		.select([
			'comic_volume_title.id',
			'comic_volume_title.created_at',
			'comic_volume_title.updated_at',
			'comic_volume_title.ulid',
			'language.lang as language_lang',
			'comic_volume_title.content',
			'comic_volume_title.is_synonym',
			'comic_volume_title.is_latinized'
		])
		.$if((param.comicID ?? 0) > 0, (qb) => {
			return qb.where('comic_id', '=', param.comicID ?? 0);
		})
		.$if((param.comicCode ?? '') != '', (qb) => {
			return qb
				.innerJoin('comic', 'comic.id', 'comic_volume_title.comic_id')
				.where('comic.code', '=', param.comicCode ?? '');
		})
		.$if((param.volumeID ?? 0) > 0, (qb) => {
			return qb.where('volume_id', '=', param.volumeID ?? 0);
		})
		.$if((param.ulid ?? '') != '', (qb) => {
			return qb.where('ulid', '=', param.ulid ?? '');
		})
		.execute()
		.catch(catchExecption);

	return result.map((result) => {
		return {
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			ulid: result.ulid,
			languageLang: result.language_lang,
			content: result.content,
			isSynonym: result.is_synonym,
			isLatinized: result.is_latinized
		};
	});
}

export async function countComicVolumeTitle(
	param: model.ParameterComicVolumeTitle
): Promise<number> {
	const { count } = await database
		.selectFrom('comic_volume_title')
		.select((eb) => eb.fn.countAll<number>().as('count'))
		.$if((param.comicID ?? 0) > 0, (qb) => {
			return qb.where('comic_id', '=', param.comicID ?? 0);
		})
		.$if((param.comicCode ?? '') != '', (qb) => {
			return qb
				.innerJoin('comic', 'comic.id', 'comic_volume_title.comic_id')
				.where('comic.code', '=', param.comicCode ?? '');
		})
		.$if((param.volumeID ?? 0) > 0, (qb) => {
			return qb.where('volume_id', '=', param.volumeID ?? 0);
		})
		.$if((param.ulid ?? '') != '', (qb) => {
			return qb.where('ulid', '=', param.ulid ?? '');
		})
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return count;
}

// Comic Volume Cover

interface ComicVolumeCoverTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	comic_id: number;
	volume_id: number;
	ulid: string;
	link_id: number;
	hint: string;
}

export async function selectComicVolumeCover(
	param: model.ParameterComicVolumeCover
): Promise<model.ComicVolumeCover[]> {
	const result = await database
		.selectFrom('comic_volume_cover')
		.innerJoin('link', 'link.id', 'link_id')
		.innerJoin('website as link_website', 'link_website.id', 'link.website_id')
		.select([
			'comic_volume_cover.id',
			'comic_volume_cover.created_at',
			'comic_volume_cover.updated_at',
			'comic_volume_cover.ulid',
			'link_website.host as link_website_host',
			'link.relative_reference as link_relative_reference',
			'comic_volume_cover.hint'
		])
		.$if((param.comicID ?? 0) > 0, (qb) => {
			return qb.where('comic_id', '=', param.comicID ?? 0);
		})
		.$if((param.comicCode ?? '') != '', (qb) => {
			return qb
				.innerJoin('comic', 'comic.id', 'comic_volume_cover.comic_id')
				.where('comic.code', '=', param.comicCode ?? '');
		})
		.$if((param.volumeID ?? 0) > 0, (qb) => {
			return qb.where('volume_id', '=', param.volumeID ?? 0);
		})
		.$if((param.ulid ?? '') != '', (qb) => {
			return qb.where('ulid', '=', param.ulid ?? '');
		})
		.execute()
		.catch(catchExecption);

	return result.map((result) => {
		return {
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			ulid: result.ulid,
			linkWebsiteHost: result.link_website_host,
			linkRelativeReference: result.link_relative_reference,
			hint: result.hint
		};
	});
}

export async function countComicVolumeCover(
	param: model.ParameterComicVolumeCover
): Promise<number> {
	const { count } = await database
		.selectFrom('comic_volume_cover')
		.select((eb) => eb.fn.countAll<number>().as('count'))
		.$if((param.comicID ?? 0) > 0, (qb) => {
			return qb.where('comic_id', '=', param.comicID ?? 0);
		})
		.$if((param.comicCode ?? '') != '', (qb) => {
			return qb
				.innerJoin('comic', 'comic.id', 'comic_volume_cover.comic_id')
				.where('comic.code', '=', param.comicCode ?? '');
		})
		.$if((param.volumeID ?? 0) > 0, (qb) => {
			return qb.where('volume_id', '=', param.volumeID ?? 0);
		})
		.$if((param.ulid ?? '') != '', (qb) => {
			return qb.where('ulid', '=', param.ulid ?? '');
		})
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return count;
}

// Comic Category

interface ComicCategoryTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	comic_id: number;
	category_id: number;
}

export async function selectComicCategory(
	param: model.ParameterComicCategory
): Promise<model.ComicCategory[]> {
	const result = await database
		.selectFrom('comic_category')
		.innerJoin('category', 'category.id', 'category_id')
		.innerJoin('category_type', 'category_type.id', 'category.type_id')
		.select([
			'comic_category.id',
			'comic_category.created_at',
			'comic_category.updated_at',
			'category_type.code as category_type_code',
			'category.code as category_code'
		])
		.$if((param.comicID ?? 0) > 0, (qb) => {
			return qb.where('comic_id', '=', param.comicID ?? 0);
		})
		.$if((param.comicCode ?? '') != '', (qb) => {
			return qb
				.innerJoin('comic', 'comic.id', 'comic_category.comic_id')
				.where('comic.code', '=', param.comicCode ?? '');
		})
		.execute()
		.catch(catchExecption);

	return result.map((result) => {
		return {
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			categoryTypeCode: result.category_type_code,
			categoryCode: result.category_code
		};
	});
}

export async function countComicCategory(param: model.ParameterComicCategory): Promise<number> {
	const { count } = await database
		.selectFrom('comic_category')
		.select((eb) => eb.fn.countAll<number>().as('count'))
		.$if((param.comicID ?? 0) > 0, (qb) => {
			return qb.where('comic_id', '=', param.comicID ?? 0);
		})
		.$if((param.comicCode ?? '') != '', (qb) => {
			return qb
				.innerJoin('comic', 'comic.id', 'comic_category.comic_id')
				.where('comic.code', '=', param.comicCode ?? '');
		})
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return count;
}

// Comic Tag

interface ComicTagTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	comic_id: number;
	tag_id: number;
}

export async function selectComicTag(param: model.ParameterComicTag): Promise<model.ComicTag[]> {
	const result = await database
		.selectFrom('comic_tag')
		.innerJoin('tag', 'tag.id', 'tag_id')
		.innerJoin('tag_type', 'tag_type.id', 'tag.type_id')
		.select([
			'comic_tag.id',
			'comic_tag.created_at',
			'comic_tag.updated_at',
			'tag_type.code as tag_type_code',
			'tag.code as tag_code'
		])
		.$if((param.comicID ?? 0) > 0, (qb) => {
			return qb.where('comic_id', '=', param.comicID ?? 0);
		})
		.$if((param.comicCode ?? '') != '', (qb) => {
			return qb
				.innerJoin('comic', 'comic.id', 'comic_tag.comic_id')
				.where('comic.code', '=', param.comicCode ?? '');
		})
		.execute()
		.catch(catchExecption);

	return result.map((result) => {
		return {
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			tagTypeCode: result.tag_type_code,
			tagCode: result.tag_code
		};
	});
}

export async function countComicTag(param: model.ParameterComicTag): Promise<number> {
	const { count } = await database
		.selectFrom('comic_category')
		.select((eb) => eb.fn.countAll<number>().as('count'))
		.$if((param.comicID ?? 0) > 0, (qb) => {
			return qb.where('comic_id', '=', param.comicID ?? 0);
		})
		.$if((param.comicCode ?? '') != '', (qb) => {
			return qb
				.innerJoin('comic', 'comic.id', 'comic_category.comic_id')
				.where('comic.code', '=', param.comicCode ?? '');
		})
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return count;
}

// Comic Relation

interface ComicRelationTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	comic_id: number;
	type_id: number;
	child_id: number;
}

export async function selectComicRelation(
	param: model.ParameterComicRelation
): Promise<model.ComicRelation[]> {
	const result = await database
		.selectFrom('comic_relation')
		.innerJoin('comic_relation_type', 'comic_relation_type.id', 'comic_relation.type_id')
		.innerJoin('comic as child', 'child.id', 'comic_relation.child_id')
		.select([
			'comic_relation.id',
			'comic_relation.created_at',
			'comic_relation.updated_at',
			'comic_relation_type.code as type_code',
			'child.code as child_code'
		])
		.$if((param.comicID ?? 0) > 0, (qb) => {
			return qb.where('comic_id', '=', param.comicID ?? 0);
		})
		.$if((param.comicCode ?? '') != '', (qb) => {
			return qb
				.innerJoin('comic', 'comic.id', 'comic_relation.comic_id')
				.where('comic.code', '=', param.comicCode ?? '');
		})
		.execute()
		.catch(catchExecption);

	return result.map((result) => {
		return {
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			typeCode: result.type_code,
			childCode: result.child_code
		};
	});
}

export async function countComicRelation(param: model.ParameterComicRelation): Promise<number> {
	const { count } = await database
		.selectFrom('comic_category')
		.select((eb) => eb.fn.countAll<number>().as('count'))
		.$if((param.comicID ?? 0) > 0, (qb) => {
			return qb.where('comic_id', '=', param.comicID ?? 0);
		})
		.$if((param.comicCode ?? '') != '', (qb) => {
			return qb
				.innerJoin('comic', 'comic.id', 'comic_category.comic_id')
				.where('comic.code', '=', param.comicCode ?? '');
		})
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return count;
}

// + Comic Author Position

interface ComicAuthorPositionTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	code: string;
	name: string;
}

export async function insertComicAuthorPosition(
	db: DB,
	data: model.NewComicAuthorPosition
): Promise<model.ComicAuthorPosition> {
	const result = await db
		.insertInto('comic_author_position')
		.values({
			code: data.code,
			name: data.name
		})
		.returningAll()
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function selectComicAuthorPosition(
	db: DB,
	param: model.ParameterComicAuthorPosition
): Promise<model.ComicAuthorPosition[]> {
	let query = db.selectFrom('comic_author_position').selectAll();

	const ct0 = param.criteriaCodes ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('code', '=', ct0[0]);
		} else {
			query = query.where('code', 'in', ct0);
		}
	}

	const obs = param.orderBys ?? [];
	if (obs.length > 0) {
		const { ref } = db.dynamic;

		obs.forEach((v) => {
			let name;
			switch (v.name) {
				case 'createdAt':
					name = 'created_at';
					break;
				case 'updatedAt':
					name = 'updated_at';
					break;
				case 'code':
				case 'name':
					name = v.name;
					break;
				default:
					return;
			}

			query = query.orderBy(ref(name), (ob) => orderByItemHelper(ob, v));
		});
	} else {
		query = query.orderBy('code');
	}

	const pgl = param.limit ?? 10;
	if (pgl > 0) {
		query = query.limit(param.limit ?? 0);
	}

	const pgo = param.offset ?? 0;
	if (!param.page && pgo > 0) {
		query = query.offset(param.offset ?? 0);
	}

	const pgp = param.page ?? 1;
	if (pgp > 1) {
		query = query.offset(pgl * (pgp - 1) + pgo);
	}

	const result = await query.execute().catch(catchExecption);

	return result.map((result) => {
		return {
			id: result.id,
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			code: result.code,
			name: result.name
		};
	});
}

export async function selectComicAuthorPositionByKey(
	db: DB,
	code: string
): Promise<model.ComicAuthorPosition | undefined> {
	const result = await db
		.selectFrom('comic_author_position')
		.where('code', '=', code)
		.selectAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function updateComicAuthorPositionByKey(
	db: DB,
	code: string,
	data: model.SetComicAuthorPosition
): Promise<model.ComicAuthorPosition | undefined> {
	const result = await db
		.updateTable('comic_author_position')
		.set({
			updated_at: new Date(),
			code: data.code,
			name: data.name
		})
		.where('code', '=', code)
		.returningAll()
		.executeTakeFirst()
		.catch(catchExecption);

	if (!result) return undefined;

	return {
		id: result.id,
		createdAt: result.created_at,
		updatedAt: result.updated_at,
		code: result.code,
		name: result.name
	};
}

export async function deleteComicAuthorPositionByKey(db: DB, code: string): Promise<boolean> {
	const result = await db
		.deleteFrom('comic_author_position')
		.where('code', '=', code)
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return result.numDeletedRows > 0;
}

export async function countComicAuthorPosition(
	db: DB,
	param: model.ParameterComicAuthorPosition
): Promise<number> {
	let query = db
		.selectFrom('comic_author_position')
		.select((eb) => eb.fn.countAll<number>().as('count'));

	const ct0 = param.criteriaCodes ?? [];
	if (ct0.length > 0) {
		if (ct0.length == 1) {
			query = query.where('code', '=', ct0[0]);
		} else {
			query = query.where('code', 'in', ct0);
		}
	}

	const { count } = await query.executeTakeFirstOrThrow().catch(catchExecption);

	return count;
}

// + Comic Relation Type

interface ComicRelationTypeTable {
	id: Generated<number>;
	created_at: ColumnType<Date, Date | undefined, never>;
	updated_at: Date | null;
	code: string;
	name: string;
}

export async function selectComicRelationType(): Promise<model.ComicRelationType[]> {
	const result = await database
		.selectFrom('comic_relation_type')
		.selectAll()
		.execute()
		.catch(catchExecption);

	return result.map((result) => {
		return {
			createdAt: result.created_at,
			updatedAt: result.updated_at,
			code: result.code,
			name: result.name
		};
	});
}

export async function countComicRelationType(): Promise<number> {
	const { count } = await database
		.selectFrom('comic_relation_type')
		.select((eb) => eb.fn.countAll<number>().as('count'))
		.executeTakeFirstOrThrow()
		.catch(catchExecption);

	return count;
}
