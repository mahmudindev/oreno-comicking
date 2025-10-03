import type { ULID } from 'ulid';
import { appendErrorMessage, ValidationError } from '$lib/exception';
import { isValid as isValidULID } from 'ulid';
import { HREF } from '$lib/helper';

//
// General
//

export interface Context {
	user: User | null;
}

export interface User {
	getUsername(): string;
}

export interface QueryParameter {
	limit?: number;
	offset?: number;
	page?: number;
	orderBys?: OrderBy[];
	criteria?: Record<string, unknown>;
}

export function validateQueryParameter(v: QueryParameter) {
	if (v.orderBys) {
		v.orderBys.forEach((v, k) => {
			try {
				validateOrderBy(v);
			} catch (e) {
				if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' order by ');

				throw e;
			}
		});
	}

	for (const k in v.criteria) {
		if (k == '') {
			throw new ValidationError('criteria ' + k + ' cannot be empty');
		}

		if (k.length > 32) {
			throw new ValidationError('criteria ' + k + ' cannot be greater than 32 characters');
		}

		if (k.length < 1) {
			throw new ValidationError('criteria ' + k + ' cannot be less than 1 characters');
		}
	}
}

export interface OrderBy {
	name: string;
	order?: string;
	nulls?: string;
	custom?: Record<string, unknown>;
}

export function validateOrderBy(v: OrderBy) {
	if (v.name == '') {
		throw new ValidationError('name cannot be empty');
	}

	if (v.name.length > 32) {
		throw new ValidationError('name cannot be greater than 32 characters');
	}

	if (v.name.length < 1) {
		throw new ValidationError('name cannot be less than 1 characters');
	}

	if (v.order) {
		switch (v.order.toLowerCase()) {
			case 'asc':
			case 'ascending':
			case 'desc':
			case 'descending':
				break;
			default:
				throw new ValidationError('order ' + v.order + ' are not supported');
		}
	}

	if (v.nulls) {
		switch (v.nulls.toLowerCase()) {
			case 'first':
			case 'last':
				break;
			default:
				throw new ValidationError('nulls ' + v.nulls + ' are not supported');
		}
	}

	for (const k in v.custom) {
		if (k == '') {
			throw new ValidationError('custom ' + k + ' cannot be empty');
		}

		if (k.length > 32) {
			throw new ValidationError('custom ' + k + ' cannot be greater than 32 characters');
		}

		if (k.length < 1) {
			throw new ValidationError('custom ' + k + ' cannot be less than 1 characters');
		}
	}
}

//
// Language
//

export interface Language {
	createdAt: Date;
	updatedAt: Date | null;
	lang: string;
	name: string;
}

export function validateLanguageKey(lang: string) {
	if (lang == '') {
		throw new ValidationError('lang cannot be empty');
	}

	if (lang.length > 16) {
		throw new ValidationError('lang cannot be greater than 16 characters');
	}

	if (lang.length < 1) {
		throw new ValidationError('lang cannot be less than 1 characters');
	}
}

export interface NewLanguage {
	lang: string;
	name: string;
}

export interface SetLanguage {
	lang?: string;
	name?: string;
}

export interface ParameterLanguage extends QueryParameter {
	criteriaLangs?: string[];
}

export function validateParameterLanguage(v: ParameterLanguage) {
	v.criteriaLangs?.forEach((v, k) => {
		try {
			validateLanguageKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

//
// Website
//

export interface Website {
	createdAt: Date;
	updatedAt: Date | null;
	host: string;
	name: string | null;
	linkCount: number;
}

export function validateWebsiteKey(host: string) {
	if (host == '') {
		throw new ValidationError('host cannot be empty');
	}

	if (host.length > 64) {
		throw new ValidationError('host cannot be greater than 64 characters');
	}

	if (host.length < 1) {
		throw new ValidationError('host cannot be less than 1 characters');
	}
}

export interface NewWebsite {
	host: string;
	name?: string;
}

export interface SetWebsite {
	host?: string;
	name?: string;
}

export interface ParameterWebsite extends QueryParameter {
	criteriaHosts?: string[];
}

export function validateParameterWebsite(v: ParameterWebsite) {
	v.criteriaHosts?.forEach((v, k) => {
		try {
			validateWebsiteKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

//
// Link
//

export interface Link {
	createdAt: Date;
	updatedAt: Date | null;
	websiteHost: string;
	websiteName: string | null;
	relativeReference: string;
}

export function validateLinkKey(websiteHost: string, relativeReference: string) {
	try {
		validateWebsiteKey(websiteHost);
	} catch (e) {
		if (e instanceof ValidationError) appendErrorMessage(e, 'website ');

		throw e;
	}

	validateLinkRelativeReference(relativeReference);
}

function validateLinkRelativeReference(v: string) {
	if (v == '') {
		throw new ValidationError('relative reference cannot be empty');
	}

	if (v.length > 255) {
		throw new ValidationError('relative reference cannot be greater than 255 characters');
	}

	if (v.length < 1) {
		throw new ValidationError('relative reference cannot be less than 1 characters');
	}

	if (!v.match(/^\/|\?|#/)) {
		throw new ValidationError('relative reference does not conform standart');
	}
}

export interface NewLink {
	websiteHost: string;
	relativeReference?: string;
}

export interface SetLink {
	websiteHost?: string;
	relativeReference?: string;
}

export interface ParameterLink extends QueryParameter {
	criteriaWebsiteHosts?: string[];
	criteriaRelativeReferences?: string[];
	criteriaHREFs?: string[];
}

export function validateParameterLink(v: ParameterLink) {
	v.criteriaWebsiteHosts?.forEach((v, k) => {
		try {
			validateWebsiteKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' website ');

			throw e;
		}
	});

	v.criteriaRelativeReferences?.forEach((v, k) => {
		try {
			validateLinkRelativeReference(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	v.criteriaHREFs?.forEach((v, k) => {
		try {
			const href = new HREF(v);

			validateLinkKey(href.getHost() ?? '', href.getRelativeReference() ?? '/');
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' href ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

//
// Character
//

export interface Character {
	createdAt: Date;
	updatedAt: Date | null;
	code: string;
	name: string;
}

export function validateCharacterKey(code: string) {
	if (code == '') {
		throw new ValidationError('code cannot be empty');
	}

	if (code.length > 12) {
		throw new ValidationError('code cannot be greater than 12 characters');
	}

	if (code.length < 1) {
		throw new ValidationError('code cannot be less than 1 characters');
	}
}

export interface NewCharacter {
	code?: string;
	name: string;
}

export interface SetCharacter {
	code?: string;
	name?: string;
}

export interface ParameterCharacter extends QueryParameter {
	criteriaCodes?: string[];
}

export function validateParameterCharacter(v: ParameterCharacter) {
	v.criteriaCodes?.forEach((v, k) => {
		try {
			validateCharacterKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

//
// Person
//

export interface Person {
	createdAt: Date;
	updatedAt: Date | null;
	code: string;
	name: string;
}

export function validatePersonKey(code: string) {
	if (code == '') {
		throw new ValidationError('code cannot be empty');
	}

	if (code.length > 12) {
		throw new ValidationError('code cannot be greater than 12 characters');
	}

	if (code.length < 1) {
		throw new ValidationError('code cannot be less than 1 characters');
	}
}

export interface NewPerson {
	code?: string;
	name: string;
}

export interface SetPerson {
	code?: string;
	name?: string;
}

export interface ParameterPerson extends QueryParameter {
	criteriaCodes?: string[];
}

export function validateParameterPerson(v: ParameterPerson) {
	v.criteriaCodes?.forEach((v, k) => {
		try {
			validatePersonKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

//
// Magazine
//

export interface Magazine {
	createdAt: Date;
	updatedAt: Date | null;
	code: string;
	name: string;
}

export function validateMagazineKey(code: string) {
	if (code == '') {
		throw new ValidationError('code cannot be empty');
	}

	if (code.length > 12) {
		throw new ValidationError('code cannot be greater than 12 characters');
	}

	if (code.length < 1) {
		throw new ValidationError('code cannot be less than 1 characters');
	}
}

export interface NewMagazine {
	code?: string;
	name: string;
}

export interface SetMagazine {
	code?: string;
	name?: string;
}

export interface ParameterMagazine extends QueryParameter {
	criteriaCodes?: string[];
}

export function validateParameterMagazine(v: ParameterMagazine) {
	v.criteriaCodes?.forEach((v, k) => {
		try {
			validateMagazineKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

//
// Category
//

export interface Category {
	createdAt: Date;
	updatedAt: Date | null;
	typeCode: string;
	code: string;
	name: string;
	parentCode: string | null;
	childCount: number;
}

export function validateCategoryKey(typeCode: string, code: string) {
	try {
		validateCategoryTypeKey(typeCode);
	} catch (e) {
		if (e instanceof ValidationError) appendErrorMessage(e, 'type ');

		throw e;
	}

	validateCategoryCode(code);
}

function validateCategoryCode(code: string) {
	if (code == '') {
		throw new ValidationError('code cannot be empty');
	}

	if (code.length > 32) {
		throw new ValidationError('code cannot be greater than 32 characters');
	}

	if (code.length < 1) {
		throw new ValidationError('code cannot be less than 1 characters');
	}
}

export interface NewCategory {
	typeCode: string;
	code: string;
	name: string;
	parentCode?: string;
}

export interface SetCategory {
	typeCode?: string;
	code?: string;
	name?: string;
	parentCode?: string;
}

export interface ParameterCategory extends QueryParameter {
	criteriaTypeCodes?: string[];
	criteriaCodes?: string[];
	criteriaParentCodes?: string[];
}

export function validateParameterCategory(v: ParameterCategory) {
	v.criteriaTypeCodes?.forEach((v, k) => {
		try {
			validateCategoryTypeKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' type ');

			throw e;
		}
	});

	v.criteriaCodes?.forEach((v, k) => {
		try {
			validateCategoryCode(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	v.criteriaParentCodes?.forEach((v, k) => {
		try {
			validateCategoryCode(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' parent ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

// + Category Type

export interface CategoryType {
	createdAt: Date;
	updatedAt: Date | null;
	code: string;
	name: string;
}

export function validateCategoryTypeKey(code: string) {
	if (code == '') {
		throw new ValidationError('code cannot be empty');
	}

	if (code.length > 32) {
		throw new ValidationError('code cannot be greater than 32 characters');
	}

	if (code.length < 1) {
		throw new ValidationError('code cannot be less than 1 characters');
	}
}

export interface NewCategoryType {
	code: string;
	name: string;
}

export interface SetCategoryType {
	code?: string;
	name?: string;
}

export interface ParameterCategoryType extends QueryParameter {
	criteriaCodes?: string[];
}

export function validateParameterCategoryType(v: ParameterCategoryType) {
	v.criteriaCodes?.forEach((v, k) => {
		try {
			validateCategoryTypeKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

//
// Tag
//

export interface Tag {
	createdAt: Date;
	updatedAt: Date | null;
	typeCode: string;
	code: string;
	name: string;
}

export function validateTagKey(typeCode: string, code: string) {
	try {
		validateTagTypeKey(typeCode);
	} catch (e) {
		if (e instanceof ValidationError) appendErrorMessage(e, 'type ');

		throw e;
	}

	validateTagCode(code);
}

function validateTagCode(code: string) {
	if (code == '') {
		throw new ValidationError('code cannot be empty');
	}

	if (code.length > 32) {
		throw new ValidationError('code cannot be greater than 32 characters');
	}

	if (code.length < 1) {
		throw new ValidationError('code cannot be less than 1 characters');
	}
}

export interface NewTag {
	typeCode: string;
	code: string;
	name: string;
}

export interface SetTag {
	typeCode?: string;
	code?: string;
	name?: string;
}

export interface ParameterTag extends QueryParameter {
	criteriaTypeCodes?: string[];
	criteriaCodes?: string[];
}

export function validateParameterTag(v: ParameterTag) {
	v.criteriaTypeCodes?.forEach((v, k) => {
		try {
			validateCategoryTypeKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' type ');

			throw e;
		}
	});

	v.criteriaCodes?.forEach((v, k) => {
		try {
			validateCategoryCode(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

// + Tag Type

export interface TagType {
	createdAt: Date;
	updatedAt: Date | null;
	code: string;
	name: string;
}

export function validateTagTypeKey(code: string) {
	if (code == '') {
		throw new ValidationError('code cannot be empty');
	}

	if (code.length > 32) {
		throw new ValidationError('code cannot be greater than 32 characters');
	}

	if (code.length < 1) {
		throw new ValidationError('code cannot be less than 1 characters');
	}
}

export interface NewTagType {
	code: string;
	name: string;
}

export interface SetTagType {
	code?: string;
	name?: string;
}

export interface ParameterTagType extends QueryParameter {
	criteriaCodes?: string[];
}

export function validateParameterTagType(v: ParameterTagType) {
	v.criteriaCodes?.forEach((v, k) => {
		try {
			validateCategoryTypeKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

//
// Comic
//

export interface Comic {
	createdAt: Date;
	updatedAt: Date | null;
	code: string;
	publishedFrom: Date | null;
	publishedTo: Date | null;
	totalChapter: number | null;
	totalVolume: number | null;
	nsfw: number | null;
	nsfl: number | null;
	titleCount: number;
	coverCount: number;
	synopsisCount: number;
	characterCount: number;
	authorCount: number;
	serializationCount: number;
	externalCount: number;
	chapterCount: number;
	categoryCount: number;
	tagCount: number;
	relationCount: number;
}

export function validateComicKey(code: string) {
	if (code == '') {
		throw new ValidationError('code cannot be empty');
	}

	if (code.length > 12) {
		throw new ValidationError('code cannot be greater than 12 characters');
	}

	if (code.length < 1) {
		throw new ValidationError('code cannot be less than 1 characters');
	}
}

export interface NewComic {
	code?: string;
	publishedFrom?: Date;
	publishedTo?: Date;
	totalChapter?: number;
	totalVolume?: number;
	nsfw?: number;
	nsfl?: number;
}

export interface SetComic {
	code?: string;
	publishedFrom?: Date;
	publishedTo?: Date;
	totalChapter?: number;
	totalVolume?: number;
	nsfw?: number;
	nsfl?: number;
}

export interface ParameterComic extends QueryParameter {
	criteriaCodes?: string[];
	criteriaExternals?: ParameterComicExternal[];
}

export function validateParameterComic(v: ParameterComic) {
	v.criteriaCodes?.forEach((v, k) => {
		try {
			validateComicKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	v.criteriaExternals?.forEach((v, k) => {
		try {
			validateParameterComicExternal(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' external ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

// Comic Title

export interface ComicTitle {
	createdAt: Date;
	updatedAt: Date | null;
	comicCode?: string;
	ulid: ULID;
	languageLang: string;
	content: string;
	isSynonym: boolean | null;
	isLatinized: boolean | null;
}

export function validateComicTitleKey(comicCode: string | null, ulid: ULID) {
	if (comicCode != null) {
		try {
			validateComicKey(comicCode);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'comic ');

			throw e;
		}
	}

	if (isValidULID(ulid)) {
		throw new ValidationError('ulid is not valid');
	}
}

export interface NewComicTitle {
	comicCode: string;
	languageLang: string;
	content: string;
	isSynonym?: boolean;
	isLatinized?: boolean;
}

export interface SetComicTitle {
	comicCode?: string;
	languageLang?: string;
	content?: string;
	isSynonym?: boolean;
	isLatinized?: boolean;
}

export interface ParameterComicTitle extends QueryParameter {
	criteriaComicCodes?: string[];
	criteriaULIDs?: ULID[];
}

export function validateParameterComicTitle(v: ParameterComicTitle) {
	v.criteriaComicCodes?.forEach((v, k) => {
		try {
			validateComicKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' comic ');

			throw e;
		}
	});

	v.criteriaULIDs?.forEach((v, k) => {
		try {
			validateComicTitleKey(null, v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

// Comic Cover

export interface ComicCover {
	createdAt: Date;
	updatedAt: Date | null;
	comicCode?: string;
	ulid: string;
	linkWebsiteHost: string;
	linkRelativeReference: string;
	hint: string | null;
}

export function validateComicCoverKey(comicCode: string | null, ulid: ULID) {
	if (comicCode != null) {
		try {
			validateComicKey(comicCode);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'comic ');

			throw e;
		}
	}

	if (isValidULID(ulid)) {
		throw new ValidationError('ulid is not valid');
	}
}

export interface NewComicCover {
	comicCode: string;
	linkWebsiteHost: string;
	linkRelativeReference?: string;
	hint?: string;
}

export interface SetComicCover {
	comicCode?: string;
	linkWebsiteHost?: string;
	linkRelativeReference?: string;
	hint?: string;
}

export interface ParameterComicCover extends QueryParameter {
	criteriaComicCodes?: string[];
	criteriaULIDs?: ULID[];
}

export function validateParameterComicCover(v: ParameterComicCover) {
	v.criteriaComicCodes?.forEach((v, k) => {
		try {
			validateComicKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' comic ');

			throw e;
		}
	});

	v.criteriaULIDs?.forEach((v, k) => {
		try {
			validateComicCoverKey(null, v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

// Comic Synopsis

export interface ComicSynopsis {
	createdAt: Date;
	updatedAt: Date | null;
	comicCode?: string;
	ulid: ULID;
	languageLang: string;
	content: string;
	source: string | null;
}

export function validateComicSynopsisKey(comicCode: string | null, ulid: ULID) {
	if (comicCode != null)
		try {
			validateComicKey(comicCode);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'comic ');

			throw e;
		}

	if (isValidULID(ulid)) {
		throw new ValidationError('ulid is not valid');
	}
}

export interface NewComicSynopsis {
	comicCode: string;
	languageLang: string;
	content: string;
	source?: string;
}

export interface SetComicSynopsis {
	comicCode?: string;
	languageLang?: string;
	content?: string;
	source?: string;
}

export interface ParameterComicSynopsis extends QueryParameter {
	criteriaComicCodes?: string[];
	criteriaULIDs?: ULID[];
}

export function validateParameterComicSynopsis(v: ParameterComicSynopsis) {
	v.criteriaComicCodes?.forEach((v, k) => {
		try {
			validateComicKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' comic ');

			throw e;
		}
	});

	v.criteriaULIDs?.forEach((v, k) => {
		try {
			validateComicTitleKey(null, v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

// Comic Character

export interface ComicCharacter {
	createdAt: Date;
	updatedAt: Date | null;
	comicCode?: string;
	characterCode: string;
	isMain: boolean | null;
}

export function validateComicCharacterKey(comicCode: string | null, characterCode: string) {
	if (comicCode != null) {
		try {
			validateComicKey(comicCode);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'comic ');

			throw e;
		}
	}

	try {
		validateCharacterKey(characterCode);
	} catch (e) {
		if (e instanceof ValidationError) appendErrorMessage(e, 'character ');

		throw e;
	}
}

export interface NewComicCharacter {
	comicCode: string;
	characterCode: string;
	isMain?: boolean;
}

export interface SetComicCharacter {
	comicCode?: string;
	characterCode?: string;
	isMain?: boolean;
}

export interface ParameterComicCharacter extends QueryParameter {
	criteriaComicCodes?: string[];
	criteriaCharacterCodes?: string[];
}

export function validateParameterComicCharacter(v: ParameterComicCharacter) {
	v.criteriaComicCodes?.forEach((v, k) => {
		try {
			validateComicKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' comic ');

			throw e;
		}
	});

	v.criteriaCharacterCodes?.forEach((v, k) => {
		try {
			validateCharacterKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' character ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

// Comic Author

export interface ComicAuthor {
	createdAt: Date;
	updatedAt: Date | null;
	comicCode?: string;
	positionCode: string;
	personCode: string;
}

export function validateComicAuthorKey(
	comicCode: string | null,
	positionCode: string,
	personCode: string
) {
	if (comicCode != null) {
		try {
			validateComicKey(comicCode);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'comic ');

			throw e;
		}
	}

	try {
		validateComicAuthorPositionKey(positionCode);
	} catch (e) {
		if (e instanceof ValidationError) appendErrorMessage(e, 'position ');

		throw e;
	}

	try {
		validatePersonKey(personCode);
	} catch (e) {
		if (e instanceof ValidationError) appendErrorMessage(e, 'person ');

		throw e;
	}
}

export interface NewComicAuthor {
	comicCode: string;
	positionCode: string;
	personCode: string;
}

export interface SetComicAuthor {
	comicCode?: string;
	positionCode?: string;
	personCode?: string;
}

export interface ParameterComicAuthor extends QueryParameter {
	criteriaComicCodes?: string[];
	criteriaPositionCodes?: string[];
	criteriaPersonCodes?: string[];
}

export function validateParameterComicAuthor(v: ParameterComicAuthor) {
	v.criteriaComicCodes?.forEach((v, k) => {
		try {
			validateComicKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' comic ');

			throw e;
		}
	});

	v.criteriaPositionCodes?.forEach((v, k) => {
		try {
			validateComicAuthorPositionKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' position ');

			throw e;
		}
	});

	v.criteriaPersonCodes?.forEach((v, k) => {
		try {
			validatePersonKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' person ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

// Comic Serialization

export interface ComicSerialization {
	createdAt: Date;
	updatedAt: Date | null;
	comicCode?: string;
	magazineCode: string;
}

export function validateComicSerializationKey(comicCode: string | null, magazineCode: string) {
	if (comicCode != null) {
		try {
			validateComicKey(comicCode);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'comic ');

			throw e;
		}
	}

	try {
		validateMagazineKey(magazineCode);
	} catch (e) {
		if (e instanceof ValidationError) appendErrorMessage(e, 'serialization ');

		throw e;
	}
}

export interface NewComicSerialization {
	comicCode: string;
	magazineCode: string;
}

export interface SetComicSerialization {
	comicCode?: string;
	magazineCode?: string;
}

export interface ParameterComicSerialization extends QueryParameter {
	criteriaComicCodes?: string[];
	criteriaMagazineCodes?: string[];
}

export function validateParameterComicSerialization(v: ParameterComicSerialization) {
	v.criteriaComicCodes?.forEach((v, k) => {
		try {
			validateComicKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' comic ');

			throw e;
		}
	});

	v.criteriaMagazineCodes?.forEach((v, k) => {
		try {
			validateMagazineKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' serialization ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

// Comic External

export interface ComicExternal {
	createdAt: Date;
	updatedAt: Date | null;
	comicCode?: string;
	linkWebsiteHost: string;
	linkRelativeReference: string;
	isOfficial: boolean | null;
	isCommunity: boolean | null;
}

export function validateComicExternalKey(
	comicCode: string | null,
	linkWebsiteHost: string,
	linkRelativeReference: string
) {
	if (comicCode != null) {
		try {
			validateComicKey(comicCode);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'comic ');

			throw e;
		}
	}

	try {
		validateLinkKey(linkWebsiteHost, linkRelativeReference);
	} catch (e) {
		if (e instanceof ValidationError) appendErrorMessage(e, 'link ');

		throw e;
	}
}

export interface NewComicExternal {
	comicCode: string;
	linkWebsiteHost: string;
	linkRelativeReference?: string;
	isOfficial?: boolean;
	isCommunity?: boolean;
}

export interface SetComicExternal {
	comicCode?: string;
	linkWebsiteHost?: string;
	linkRelativeReference?: string;
	isOfficial?: boolean;
	isCommunity?: boolean;
}

export interface ParameterComicExternal extends QueryParameter {
	criteriaComicCodes?: string[];
	criteriaLinkWebsiteHosts?: string[];
	criteriaLinkRelativeReferences?: string[];
	criteriaLinkHREFs?: string[];
}

export function validateParameterComicExternal(v: ParameterComicExternal) {
	v.criteriaComicCodes?.forEach((v, k) => {
		try {
			validateComicKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' comic ');

			throw e;
		}
	});

	v.criteriaLinkWebsiteHosts?.forEach((v, k) => {
		try {
			validateWebsiteKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' link website ');

			throw e;
		}
	});

	v.criteriaLinkRelativeReferences?.forEach((v, k) => {
		try {
			validateLinkRelativeReference(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' link ');

			throw e;
		}
	});

	v.criteriaLinkHREFs?.forEach((v, k) => {
		try {
			const href = new HREF(v);

			validateLinkKey(href.getHost() ?? '', href.getRelativeReference() ?? '/');
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' link href ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

// Comic Chapter

export interface ComicChapter {
	createdAt: Date;
	updatedAt: Date | null;
	comicCode?: string;
	number: number;
	version: string | null;
	releasedAt: Date | null;
	volumeNumber: number | null;
	titleCount: number;
}

export function validateComicChapterKey(
	comicCode: string | null,
	number: number,
	version: string | null
) {
	if (comicCode != null) {
		try {
			validateComicKey(comicCode);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'comic ');

			throw e;
		}
	}

	if (Number.isNaN(number)) {
		throw new ValidationError('number is not valid');
	}

	if (version != null) {
		validateComicChapterVersion(version);
	}
}

function validateComicChapterVersion(version: string) {
	if (version == '') {
		throw new ValidationError('version cannot be empty');
	}

	if (version.length > 32) {
		throw new ValidationError('version cannot be greater than 32 characters');
	}

	if (version.length < 1) {
		throw new ValidationError('version cannot be less than 1 characters');
	}
}

export interface NewComicChapter {
	comicCode: string;
	number: number;
	version?: string;
	releasedAt?: Date;
	volumeNumber?: number;
}

export interface SetComicChapter {
	comicCode?: string;
	number?: number;
	version?: string;
	releasedAt?: Date;
	volumeNumber?: number;
}

export interface ParameterComicChapter extends QueryParameter {
	criteriaComicCodes?: string[];
	criteriaNumbers?: number[];
	criteriaVersions?: string[];
	criteriaNVs?: string[];
}

export function validateParameterComicChapter(v: ParameterComicChapter) {
	v.criteriaComicCodes?.forEach((v, k) => {
		try {
			validateComicKey(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' comic ');

			throw e;
		}
	});

	v.criteriaNumbers?.forEach((v, k) => {
		try {
			validateComicChapterKey(null, v, null);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	v.criteriaVersions?.forEach((v, k) => {
		try {
			validateComicChapterVersion(v);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	v.criteriaNVs?.forEach((v, k) => {
		try {
			const nv = v.split('+');

			validateComicChapterKey(null, Number(nv[0]), nv[1] ?? null);
		} catch (e) {
			if (e instanceof ValidationError) appendErrorMessage(e, 'index ' + k + ' ');

			throw e;
		}
	});

	validateQueryParameter(v);
}

// Comic Chapter Title

export interface ComicChapterTitle {
	createdAt: Date;
	updatedAt: Date | null;
	ulid: string;
	languageLang: string;
	content: string;
	isSynonym: boolean | null;
	isLatinized: boolean | null;
}

export interface NewComicChapterTitle {
	comicCode: string;
	chapterNumber: string;
	chapterVersion?: string;
	languageLang: string;
	content: string;
	isSynonym?: boolean;
	isLatinized?: boolean;
}

export interface SetComicChapterTitle {
	comicCode?: string;
	chapterNumber?: string;
	chapterVersion?: string;
	languageLang?: string;
	content?: string;
	isSynonym?: boolean;
	isLatinized?: boolean;
}

export interface ParameterComicChapterTitle {
	comicID?: number;
	comicCode?: string;
	chapterID?: number;
	chapterNumber?: string;
	chapterVersion?: string;
	ulid?: string;
}

// Comic Volume

export interface ComicVolume {
	createdAt: Date;
	updatedAt: Date | null;
	number: number;
	releasedAt: Date | null;
	titleCount: number;
	coverCount: number;
	chapterCount: number;
}

export interface NewComicVolume {
	comicCode: string;
	number: number;
	releasedAt?: Date;
}

export interface SetComicVolume {
	comicCode?: string;
	number: number;
	releasedAt?: Date;
}

export interface ParameterComicVolume {
	comicID?: number;
	comicCode?: string;
}

// Comic Volume Title

export interface ComicVolumeTitle {
	createdAt: Date;
	updatedAt: Date | null;
	ulid: string;
	languageLang: string;
	content: string;
	isSynonym: boolean | null;
	isLatinized: boolean | null;
}

export interface NewComicVolumeTitle {
	comicCode: string;
	volumeNumber: number;
	languageLang: string;
	content: string;
	isSynonym?: boolean;
	isLatinized?: boolean;
}

export interface SetComicVolumeTitle {
	comicCode?: string;
	volumeNumber?: number;
	languageLang?: string;
	content?: string;
	isSynonym?: boolean;
	isLatinized?: boolean;
}

export interface ParameterComicVolumeTitle {
	comicID?: number;
	comicCode?: string;
	volumeID?: number;
	volumeNumber?: string;
	ulid?: string;
}

// Comic Volume Cover

export interface ComicVolumeCover {
	createdAt: Date;
	updatedAt: Date | null;
	ulid: string;
	linkWebsiteHost: string;
	linkRelativeReference: string;
	hint: string;
}

export interface NewComicVolumeCover {
	comicCode: string;
	volumeNumber: number;
	linkWebsiteHost: string;
	linkRelativeReference?: string;
	hint?: string;
}

export interface SetComicVolumeCover {
	comicCode?: string;
	volumeNumber?: number;
	linkWebsiteHost?: string;
	linkRelativeReference?: string;
	hint?: string;
}

export interface ParameterComicVolumeCover {
	comicID?: number;
	comicCode?: string;
	volumeID?: number;
	volumeNumber?: string;
	ulid?: string;
}

// Comic Category

export interface ComicCategory {
	createdAt: Date;
	updatedAt: Date | null;
	categoryTypeCode: string;
	categoryCode: string;
}

export interface NewComicCategory {
	comicCode: string;
	categoryTypeCode: string;
	categoryCode: string;
}

export interface SetComicCategory {
	comicCode?: string;
	categoryTypeCode?: string;
	categoryCode?: string;
}

export interface ParameterComicCategory {
	comicID?: number;
	comicCode?: string;
}

// Comic Tag

export interface ComicTag {
	createdAt: Date;
	updatedAt: Date | null;
	tagTypeCode: string;
	tagCode: string;
}

export interface NewComicTag {
	comicCode: string;
	tagTypeCode: string;
	tagCode: string;
}

export interface SetComicTag {
	comicCode?: string;
	tagTypeCode?: string;
	tagCode?: string;
}

export interface ParameterComicTag {
	comicID?: number;
	comicCode?: string;
}

// Comic Relation

export interface ComicRelation {
	createdAt: Date;
	updatedAt: Date | null;
	typeCode: string;
	childCode: string;
}

export interface NewComicRelation {
	comicCode: string;
	typeCode: string;
	childCode: string;
}

export interface SetComicRelation {
	comicCode?: string;
	typeCode?: string;
	childCode?: string;
}

export interface ParameterComicRelation {
	comicID?: number;
	comicCode?: string;
}

// + Comic Author Position

export interface ComicAuthorPosition {
	createdAt: Date;
	updatedAt: Date | null;
	code: string;
	name: string;
}

export function validateComicAuthorPositionKey(code: string) {
	if (code == '') {
		throw new ValidationError('code cannot be empty');
	}

	if (code.length > 32) {
		throw new ValidationError('code cannot be greater than 32 characters');
	}

	if (code.length < 1) {
		throw new ValidationError('code cannot be less than 1 characters');
	}
}

export interface NewComicAuthorPosition {
	code: string;
	name: string;
}

export interface SetComicAuthorPosition {
	code?: string;
	name?: string;
}

export interface ParameterComicAuthorPosition {
	code?: string;
}

// + Comic Relation Type

export interface ComicRelationType {
	createdAt: Date;
	updatedAt: Date | null;
	code: string;
	name: string;
}

export function validateComicRelationTypeKey(code: string) {
	if (code == '') {
		throw new ValidationError('code cannot be empty');
	}

	if (code.length > 32) {
		throw new ValidationError('code cannot be greater than 32 characters');
	}

	if (code.length < 1) {
		throw new ValidationError('code cannot be less than 1 characters');
	}
}

export interface NewComicRelationType {
	code: string;
	name: string;
}

export interface SetComicRelationType {
	code?: string;
	name?: string;
}

export interface ParameterComicRelationType {
	code?: string;
}
