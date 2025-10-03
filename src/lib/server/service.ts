import type { ULID } from 'ulid';
import { AuthError, AuthErrorType, NotFoundError } from '$lib/exception';
import { isStringArray, isStringRecordArray } from '$lib/helper';
import * as database from './database';
import * as model from './model';

//
// Language
//

export async function addLanguage(
	ctx: model.Context,
	data: model.NewLanguage
): Promise<model.Language> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'LANGUAGE'])) {
		throw new AuthError('permission denied to add language', AuthErrorType.Unauthorized);
	}

	return await database.insertLanguage(ctx.database, data);
}

export async function getLanguageByKey(ctx: model.Context, lang: string): Promise<model.Language> {
	model.validateLanguageKey(lang);

	const result = await database.selectLanguageByKey(ctx.database, lang);

	if (!result) throw new NotFoundError('language does not exist');

	return result;
}

export async function updateLanguageByKey(
	ctx: model.Context,
	lang: string,
	data: model.SetLanguage
): Promise<model.Language> {
	model.validateLanguageKey(lang);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'LANGUAGE'])) {
		throw new AuthError('permission denied to update language', AuthErrorType.Unauthorized);
	}

	const result = await database.updateLanguageByKey(ctx.database, lang, data);

	if (!result) throw new NotFoundError('language does not exist');

	return result;
}

export async function deleteLanguageByKey(ctx: model.Context, lang: string): Promise<void> {
	model.validateLanguageKey(lang);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'LANGUAGE'])) {
		throw new AuthError('permission denied to delete language', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteLanguageByKey(ctx.database, lang);

	if (!result) throw new NotFoundError('language does not exist');
}

export async function listLanguage(
	ctx: model.Context,
	param: model.ParameterLanguage
): Promise<model.Language[]> {
	model.validateParameterLanguage(param);

	return await database.selectLanguage(ctx.database, param);
}

export async function countLanguage(
	ctx: model.Context,
	param: model.ParameterLanguage
): Promise<number> {
	model.validateParameterLanguage(param);

	return await database.countLanguage(ctx.database, param);
}

//
// Website
//

export async function addWebsite(
	ctx: model.Context,
	data: model.NewWebsite
): Promise<model.Website> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'WEBSITE'])) {
		throw new AuthError('permission denied to add website', AuthErrorType.Unauthorized);
	}

	return await database.insertWebsite(ctx.database, data);
}

export async function getWebsiteByKey(ctx: model.Context, host: string): Promise<model.Website> {
	model.validateWebsiteKey(host);

	const result = await database.selectWebsiteByKey(ctx.database, host).then(async (result) => {
		if (!result) return result;

		result.linkCount = await countLink(ctx, { criteriaWebsiteIDs: [result.id] });

		return result;
	});

	if (!result) throw new NotFoundError('website does not exist');

	return result;
}

export async function updateWebsiteByKey(
	ctx: model.Context,
	host: string,
	data: model.SetWebsite
): Promise<model.Website> {
	model.validateWebsiteKey(host);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'WEBSITE'])) {
		throw new AuthError('permission denied to update website', AuthErrorType.Unauthorized);
	}

	const result = await database
		.updateWebsiteByKey(ctx.database, host, data)
		.then(async (result) => {
			if (!result) return result;

			result.linkCount = await countLink(ctx, { criteriaWebsiteIDs: [result.id] });

			return result;
		});

	if (!result) throw new NotFoundError('website does not exist');

	return result;
}

export async function deleteWebsiteByKey(ctx: model.Context, host: string): Promise<void> {
	model.validateWebsiteKey(host);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'WEBSITE'])) {
		throw new AuthError('permission denied to delete website', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteWebsiteByKey(ctx.database, host);

	if (!result) throw new NotFoundError('website does not exist');
}

export async function listWebsite(
	ctx: model.Context,
	param: model.ParameterWebsite
): Promise<model.Website[]> {
	model.validateParameterWebsite(param);

	return await database.selectWebsite(ctx.database, param).then(async (result) => {
		return await Promise.all(
			result.map(async (result) => {
				result.linkCount = await countLink(ctx, { criteriaWebsiteIDs: [result.id] });

				return result;
			})
		);
	});
}

export async function countWebsite(
	ctx: model.Context,
	param: model.ParameterWebsite
): Promise<number> {
	model.validateParameterWebsite(param);

	return await database.countWebsite(ctx.database, param);
}

//
// Link
//

export async function addLink(ctx: model.Context, data: model.NewLink): Promise<model.Link> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'LINK'])) {
		throw new AuthError('permission denied to add link', AuthErrorType.Unauthorized);
	}

	return await database.insertLink(ctx.database, data);
}

export async function getLinkByKey(
	ctx: model.Context,
	websiteHost: string,
	relativeReference: string
): Promise<model.Link> {
	model.validateLinkKey(websiteHost, relativeReference);

	const result = await database.selectLinkByKey(ctx.database, websiteHost, relativeReference);

	if (!result) throw new NotFoundError('link does not exist');

	return result;
}

export async function updateLinkByKey(
	ctx: model.Context,
	websiteHost: string,
	relativeReference: string,
	data: model.SetLink
): Promise<model.Link> {
	model.validateLinkKey(websiteHost, relativeReference);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'LINK'])) {
		throw new AuthError('permission denied to update link', AuthErrorType.Unauthorized);
	}

	const result = await database.updateLinkByKey(ctx.database, websiteHost, relativeReference, data);

	if (!result) throw new NotFoundError('link does not exist');

	return result;
}

export async function deleteLinkByKey(
	ctx: model.Context,
	websiteHost: string,
	relativeReference: string
): Promise<void> {
	model.validateLinkKey(websiteHost, relativeReference);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'LINK'])) {
		throw new AuthError('permission denied to delete link', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteLinkByKey(ctx.database, websiteHost, relativeReference);

	if (!result) throw new NotFoundError('link does not exist');
}

export async function listLink(
	ctx: model.Context,
	param: model.ParameterLink
): Promise<model.Link[]> {
	model.validateParameterLink(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['websiteHosts'])) {
			param.criteriaWebsiteHosts = param.criteria['websiteHosts'];
		}

		if (isStringArray(param.criteria['relativeReferences'])) {
			param.criteriaRelativeReferences = param.criteria['relativeReferences'];
		}

		if (isStringArray(param.criteria['hrefs'])) {
			param.criteriaHREFs = param.criteria['hrefs'];
		}
	}

	return await database.selectLink(ctx.database, param);
}

export async function countLink(ctx: model.Context, param: model.ParameterLink): Promise<number> {
	model.validateParameterLink(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['websiteHosts'])) {
			param.criteriaWebsiteHosts = param.criteria['websiteHosts'];
		}

		if (isStringArray(param.criteria['relativeReferences'])) {
			param.criteriaRelativeReferences = param.criteria['relativeReferences'];
		}

		if (isStringArray(param.criteria['hrefs'])) {
			param.criteriaHREFs = param.criteria['hrefs'];
		}
	}

	return await database.countLink(ctx.database, param);
}

//
// Character
//

export async function addCharacter(
	ctx: model.Context,
	data: model.NewCharacter
): Promise<model.Character> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'CHARACTER'])) {
		throw new AuthError('permission denied to add character', AuthErrorType.Unauthorized);
	}

	return await database.insertCharacter(ctx.database, data);
}

export async function getCharacterByKey(
	ctx: model.Context,
	code: string
): Promise<model.Character> {
	model.validateCharacterKey(code);

	const result = await database.selectCharacterByKey(ctx.database, code);

	if (!result) throw new NotFoundError('character does not exist');

	return result;
}

export async function updateCharacterByKey(
	ctx: model.Context,
	code: string,
	data: model.SetCharacter
): Promise<model.Character> {
	model.validateCharacterKey(code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'CHARACTER'])) {
		throw new AuthError('permission denied to update character', AuthErrorType.Unauthorized);
	}

	const result = await database.updateCharacterByKey(ctx.database, code, data);

	if (!result) throw new NotFoundError('character does not exist');

	return result;
}

export async function deleteCharacterByKey(ctx: model.Context, code: string): Promise<void> {
	model.validateCharacterKey(code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'CHARACTER'])) {
		throw new AuthError('permission denied to delete character', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteCharacterByKey(ctx.database, code);

	if (!result) throw new NotFoundError('character does not exist');
}

export async function listCharacter(
	ctx: model.Context,
	param: model.ParameterCharacter
): Promise<model.Character[]> {
	model.validateParameterCharacter(param);

	return await database.selectCharacter(ctx.database, param);
}

export async function countCharacter(
	ctx: model.Context,
	param: model.ParameterCharacter
): Promise<number> {
	model.validateParameterCharacter(param);

	return await database.countCharacter(ctx.database, param);
}

//
// Person
//

export async function addPerson(ctx: model.Context, data: model.NewPerson): Promise<model.Person> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'PERSON'])) {
		throw new AuthError('permission denied to add person', AuthErrorType.Unauthorized);
	}

	return await database.insertPerson(ctx.database, data);
}

export async function getPersonByKey(ctx: model.Context, code: string): Promise<model.Person> {
	model.validatePersonKey(code);

	const result = await database.selectPersonByKey(ctx.database, code);

	if (!result) throw new NotFoundError('person does not exist');

	return result;
}

export async function updatePersonByKey(
	ctx: model.Context,
	code: string,
	data: model.SetPerson
): Promise<model.Person> {
	model.validatePersonKey(code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'PERSON'])) {
		throw new AuthError('permission denied to update person', AuthErrorType.Unauthorized);
	}

	const result = await database.updatePersonByKey(ctx.database, code, data);

	if (!result) throw new NotFoundError('person does not exist');

	return result;
}

export async function deletePersonByKey(ctx: model.Context, code: string): Promise<void> {
	model.validatePersonKey(code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'PERSON'])) {
		throw new AuthError('permission denied to delete person', AuthErrorType.Unauthorized);
	}

	const result = await database.deletePersonByKey(ctx.database, code);

	if (!result) throw new NotFoundError('person does not exist');
}

export async function listPerson(
	ctx: model.Context,
	param: model.ParameterPerson
): Promise<model.Person[]> {
	model.validateParameterPerson(param);

	return await database.selectPerson(ctx.database, param);
}

export async function countPerson(
	ctx: model.Context,
	param: model.ParameterPerson
): Promise<number> {
	model.validateParameterPerson(param);

	return await database.countPerson(ctx.database, param);
}

//
// Magazine
//

export async function addMagazine(
	ctx: model.Context,
	data: model.NewMagazine
): Promise<model.Magazine> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'MAGAZINE'])) {
		throw new AuthError('permission denied to add magazone', AuthErrorType.Unauthorized);
	}

	return await database.insertMagazine(ctx.database, data);
}

export async function getMagazineByKey(ctx: model.Context, code: string): Promise<model.Magazine> {
	model.validatePersonKey(code);

	const result = await database.selectMagazineByKey(ctx.database, code);

	if (!result) throw new NotFoundError('magazine does not exist');

	return result;
}

export async function updateMagazineByKey(
	ctx: model.Context,
	code: string,
	data: model.SetMagazine
): Promise<model.Magazine> {
	model.validateMagazineKey(code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'MAGAZINE'])) {
		throw new AuthError('permission denied to update magazine', AuthErrorType.Unauthorized);
	}

	const result = await database.updateMagazineByKey(ctx.database, code, data);

	if (!result) throw new NotFoundError('magazine does not exist');

	return result;
}

export async function deleteMagazineByKey(ctx: model.Context, code: string): Promise<void> {
	model.validateMagazineKey(code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'MAGAZINE'])) {
		throw new AuthError('permission denied to delete magazine', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteMagazineByKey(ctx.database, code);

	if (!result) throw new NotFoundError('magazine does not exist');
}

export async function listMagazine(
	ctx: model.Context,
	param: model.ParameterMagazine
): Promise<model.Magazine[]> {
	model.validateParameterMagazine(param);

	return await database.selectMagazine(ctx.database, param);
}

export async function countMagazine(
	ctx: model.Context,
	param: model.ParameterMagazine
): Promise<number> {
	model.validateParameterMagazine(param);

	return await database.countMagazine(ctx.database, param);
}

//
// Category
//

export async function addCategory(
	ctx: model.Context,
	data: model.NewCategory
): Promise<model.Category> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'CATEGORY'])) {
		throw new AuthError('permission denied to add category', AuthErrorType.Unauthorized);
	}

	return await database.insertCategory(ctx.database, data);
}

export async function getCategoryByKey(
	ctx: model.Context,
	typeCode: string,
	code: string
): Promise<model.Category> {
	model.validateCategoryKey(typeCode, code);

	const result = await database
		.selectCategoryByKey(ctx.database, typeCode, code)
		.then(async (result) => {
			if (!result) return result;

			result.childCount = await countCategory(ctx, { criteriaIDs: [result.id] });

			return result;
		});

	if (!result) throw new NotFoundError('category does not exist');

	return result;
}

export async function updateCategoryByKey(
	ctx: model.Context,
	typeCode: string,
	code: string,
	data: model.SetCategory
): Promise<model.Category> {
	model.validateCategoryKey(typeCode, code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'CATEGORY'])) {
		throw new AuthError('permission denied to update category', AuthErrorType.Unauthorized);
	}

	const result = await database
		.updateCategoryByKey(ctx.database, typeCode, code, data)
		.then(async (result) => {
			if (!result) return result;

			result.childCount = await countCategory(ctx, { criteriaIDs: [result.id] });

			return result;
		});

	if (!result) throw new NotFoundError('category does not exist');

	return result;
}

export async function deleteCategoryByKey(
	ctx: model.Context,
	typeCode: string,
	code: string
): Promise<void> {
	model.validateCategoryKey(typeCode, code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'CATEGORY'])) {
		throw new AuthError('permission denied to delete category', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteCategoryByKey(ctx.database, typeCode, code);

	if (!result) throw new NotFoundError('category does not exist');
}

export async function listCategory(
	ctx: model.Context,
	param: model.ParameterCategory
): Promise<model.Category[]> {
	model.validateParameterCategory(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['typeCodes'])) {
			param.criteriaTypeCodes = param.criteria['typeCodes'];
		}

		if (isStringArray(param.criteria['parentCodes'])) {
			param.criteriaParentCodes = param.criteria['parentCodes'];
		}
	}

	return await database.selectCategory(ctx.database, param).then(async (result) => {
		return await Promise.all(
			result.map(async (result) => {
				result.childCount = await countCategory(ctx, { criteriaIDs: [result.id] });

				return result;
			})
		);
	});
}

export async function countCategory(
	ctx: model.Context,
	param: model.ParameterCategory
): Promise<number> {
	model.validateParameterCategory(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['typeCodes'])) {
			param.criteriaTypeCodes = param.criteria['typeCodes'];
		}

		if (isStringArray(param.criteria['parentCodes'])) {
			param.criteriaParentCodes = param.criteria['parentCodes'];
		}
	}

	return await database.countCategory(ctx.database, param);
}

// + Category Type

export async function addCategoryType(
	ctx: model.Context,
	data: model.NewCategoryType
): Promise<model.CategoryType> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'CATEGORYTYPE'])) {
		throw new AuthError('permission denied to add category type', AuthErrorType.Unauthorized);
	}

	return await database.insertCategoryType(ctx.database, data);
}

export async function getCategoryTypeByKey(
	ctx: model.Context,
	code: string
): Promise<model.CategoryType> {
	model.validateCategoryTypeKey(code);

	const result = await database.selectCategoryTypeByKey(ctx.database, code);

	if (!result) throw new NotFoundError('category type does not exist');

	return result;
}

export async function updateCategoryTypeByKey(
	ctx: model.Context,
	code: string,
	data: model.SetCategoryType
): Promise<model.CategoryType> {
	model.validateCategoryTypeKey(code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'CATEGORYTYPE'])) {
		throw new AuthError('permission denied to update category type', AuthErrorType.Unauthorized);
	}

	const result = await database.updateCategoryTypeByKey(ctx.database, code, data);

	if (!result) throw new NotFoundError('category type does not exist');

	return result;
}

export async function deleteCategoryTypeByKey(ctx: model.Context, code: string): Promise<void> {
	model.validateCategoryTypeKey(code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'CATEGORYTYPE'])) {
		throw new AuthError('permission denied to delete category type', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteCategoryTypeByKey(ctx.database, code);

	if (!result) throw new NotFoundError('category type does not exist');
}

export async function listCategoryType(
	ctx: model.Context,
	param: model.ParameterCategoryType
): Promise<model.CategoryType[]> {
	model.validateParameterCategoryType(param);

	return await database.selectCategoryType(ctx.database, param);
}

export async function countCategoryType(
	ctx: model.Context,
	param: model.ParameterCategoryType
): Promise<number> {
	model.validateParameterCategoryType(param);

	return await database.countCategoryType(ctx.database, param);
}

//
// Tag
//

export async function addTag(ctx: model.Context, data: model.NewTag): Promise<model.Tag> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'TAG'])) {
		throw new AuthError('permission denied to add tag', AuthErrorType.Unauthorized);
	}

	return await database.insertTag(ctx.database, data);
}

export async function getTagByKey(
	ctx: model.Context,
	typeCode: string,
	code: string
): Promise<model.Tag> {
	model.validateTagKey(typeCode, code);

	const result = await database.selectTagByKey(ctx.database, typeCode, code);

	if (!result) throw new NotFoundError('tag does not exist');

	return result;
}

export async function updateTagByKey(
	ctx: model.Context,
	typeCode: string,
	code: string,
	data: model.SetTag
): Promise<model.Tag> {
	model.validateTagKey(typeCode, code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'TAG'])) {
		throw new AuthError('permission denied to update tag', AuthErrorType.Unauthorized);
	}

	const result = await database.updateTagByKey(ctx.database, typeCode, code, data);

	if (!result) throw new NotFoundError('tag does not exist');

	return result;
}

export async function deleteTagByKey(
	ctx: model.Context,
	typeCode: string,
	code: string
): Promise<void> {
	model.validateTagKey(typeCode, code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'TAG'])) {
		throw new AuthError('permission denied to delete tag', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteTagByKey(ctx.database, typeCode, code);

	if (!result) throw new NotFoundError('tag does not exist');
}

export async function listTag(ctx: model.Context, param: model.ParameterTag): Promise<model.Tag[]> {
	model.validateParameterTag(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['typeCodes'])) {
			param.criteriaTypeCodes = param.criteria['typeCodes'];
		}
	}

	return await database.selectTag(ctx.database, param);
}

export async function countTag(ctx: model.Context, param: model.ParameterTag): Promise<number> {
	model.validateParameterTag(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['typeCodes'])) {
			param.criteriaTypeCodes = param.criteria['typeCodes'];
		}
	}

	return await database.countTag(ctx.database, param);
}

// + Tag Type

export async function addTagType(
	ctx: model.Context,
	data: model.NewTagType
): Promise<model.TagType> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'TAGTYPE'])) {
		throw new AuthError('permission denied to add tag type', AuthErrorType.Unauthorized);
	}

	return await database.insertTagType(ctx.database, data);
}

export async function getTagTypeByKey(ctx: model.Context, code: string): Promise<model.TagType> {
	model.validateTagTypeKey(code);

	const result = await database.selectTagTypeByKey(ctx.database, code);

	if (!result) throw new NotFoundError('tag type does not exist');

	return result;
}

export async function updateTagTypeByKey(
	ctx: model.Context,
	code: string,
	data: model.SetTagType
): Promise<model.TagType> {
	model.validateTagTypeKey(code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'TAGTYPE'])) {
		throw new AuthError('permission denied to update tag type', AuthErrorType.Unauthorized);
	}

	const result = await database.updateTagTypeByKey(ctx.database, code, data);

	if (!result) throw new NotFoundError('tag type does not exist');

	return result;
}

export async function deleteTagTypeByKey(ctx: model.Context, code: string): Promise<void> {
	model.validateTagTypeKey(code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'TAGTYPE'])) {
		throw new AuthError('permission denied to delete tag type', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteTagTypeByKey(ctx.database, code);

	if (!result) throw new NotFoundError('tag type does not exist');
}

export async function listTagType(
	ctx: model.Context,
	param: model.ParameterTagType
): Promise<model.TagType[]> {
	model.validateParameterTagType(param);

	return await database.selectTagType(ctx.database, param);
}

export async function countTagType(
	ctx: model.Context,
	param: model.ParameterTagType
): Promise<number> {
	model.validateParameterTagType(param);

	return await database.countTagType(ctx.database, param);
}

//
// Comic
//

export async function addComic(ctx: model.Context, data: model.NewComic): Promise<model.Comic> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMIC'])) {
		throw new AuthError('permission denied to add comic', AuthErrorType.Unauthorized);
	}

	return await database.insertComic(ctx.database, data);
}

export async function getComicByKey(ctx: model.Context, code: string): Promise<model.Comic> {
	model.validateComicKey(code);

	const result = await database.selectComicByKey(ctx.database, code).then(async (result) => {
		if (!result) return result;

		result.titleCount = await countComicTitle(ctx, { criteriaComicIDs: [result.id] });
		result.coverCount = await countComicCover(ctx, { criteriaComicIDs: [result.id] });
		result.synopsisCount = await countComicSynopsis(ctx, { criteriaComicIDs: [result.id] });
		result.characterCount = await countComicCharacter(ctx, { criteriaComicIDs: [result.id] });
		result.authorCount = await countComicAuthor(ctx, { criteriaComicIDs: [result.id] });
		result.serializationCount = await countComicSerialization(ctx, {
			criteriaComicIDs: [result.id]
		});
		result.externalCount = 0;
		result.chapterCount = 0;
		result.categoryCount = 0;
		result.tagCount = 0;
		result.relationCount = 0;

		return result;
	});

	if (!result) throw new NotFoundError('comic does not exist');

	return result;
}

export async function updateComicByKey(
	ctx: model.Context,
	code: string,
	data: model.SetComic
): Promise<model.Comic> {
	model.validateComicKey(code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMIC'])) {
		throw new AuthError('permission denied to update comic', AuthErrorType.Unauthorized);
	}

	const result = await database.updateComicByKey(ctx.database, code, data).then(async (result) => {
		if (!result) return result;

		result.titleCount = await countComicTitle(ctx, { criteriaComicIDs: [result.id] });
		result.coverCount = await countComicCover(ctx, { criteriaComicIDs: [result.id] });
		result.synopsisCount = await countComicSynopsis(ctx, { criteriaComicIDs: [result.id] });
		result.characterCount = await countComicCharacter(ctx, { criteriaComicIDs: [result.id] });
		result.authorCount = await countComicAuthor(ctx, { criteriaComicIDs: [result.id] });
		result.serializationCount = await countComicSerialization(ctx, {
			criteriaComicIDs: [result.id]
		});
		result.externalCount = 0;
		result.chapterCount = 0;
		result.categoryCount = 0;
		result.tagCount = 0;
		result.relationCount = 0;

		return result;
	});

	if (!result) throw new NotFoundError('comic does not exist');

	return result;
}

export async function deleteComicByKey(ctx: model.Context, code: string): Promise<void> {
	model.validateComicKey(code);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMIC'])) {
		throw new AuthError('permission denied to delete comic', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteComicByKey(ctx.database, code);

	if (!result) throw new NotFoundError('comic does not exist');
}

export async function listComic(
	ctx: model.Context,
	param: model.ParameterComic
): Promise<model.Comic[]> {
	model.validateParameterComic(param);

	if (param.criteria) {
		if (isStringRecordArray(param.criteria['externals'])) {
			param.criteriaExternals = param.criteria['externals'].map((v) => {
				const external: model.ParameterComicExternal = {};

				if (v['linkWebsiteHosts']) {
					//
				}

				if (v['linkRelativeReferences']) {
					//
				}

				if (v['linkHREFs']) {
					//
				}

				return external;
			});
		}
	}

	return await database.selectComic(ctx.database, param).then(async (result) => {
		return await Promise.all(
			result.map(async (result) => {
				result.titleCount = await countComicTitle(ctx, { criteriaComicIDs: [result.id] });
				result.coverCount = await countComicCover(ctx, { criteriaComicIDs: [result.id] });
				result.synopsisCount = await countComicSynopsis(ctx, { criteriaComicIDs: [result.id] });
				result.characterCount = await countComicCharacter(ctx, { criteriaComicIDs: [result.id] });
				result.authorCount = await countComicAuthor(ctx, { criteriaComicIDs: [result.id] });
				result.serializationCount = await countComicSerialization(ctx, {
					criteriaComicIDs: [result.id]
				});
				result.externalCount = 0;
				result.chapterCount = 0;
				result.categoryCount = 0;
				result.tagCount = 0;
				result.relationCount = 0;

				return result;
			})
		);
	});
}

export async function countComic(ctx: model.Context, param: model.ParameterComic): Promise<number> {
	model.validateParameterComic(param);

	return await database.countComic(ctx.database, param);
}

// Comic Title

export async function addComicTitle(
	ctx: model.Context,
	data: model.NewComicTitle
): Promise<model.ComicTitle> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICTITLE'])) {
		throw new AuthError('permission denied to add comic title', AuthErrorType.Unauthorized);
	}

	return await database.insertComicTitle(ctx.database, data);
}

export async function getComicTitleByKey(
	ctx: model.Context,
	comicCode: string,
	ulid: ULID
): Promise<model.ComicTitle> {
	model.validateComicTitleKey(comicCode, ulid);

	const result = await database.selectComicTitleByKey(ctx.database, comicCode, ulid);

	if (!result) throw new NotFoundError('comic title does not exist');

	return result;
}

export async function updateComicTitleByKey(
	ctx: model.Context,
	comicCode: string,
	ulid: ULID,
	data: model.SetComicTitle
): Promise<model.ComicTitle> {
	model.validateComicTitleKey(comicCode, ulid);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICTITLE'])) {
		throw new AuthError('permission denied to update comic title', AuthErrorType.Unauthorized);
	}

	const result = await database.updateComicTitleByKey(ctx.database, comicCode, ulid, data);

	if (!result) throw new NotFoundError('comic title does not exist');

	return result;
}

export async function deleteComicTitleByKey(
	ctx: model.Context,
	comicCode: string,
	ulid: ULID
): Promise<void> {
	model.validateComicTitleKey(comicCode, ulid);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICTITLE'])) {
		throw new AuthError('permission denied to delete comic title', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteComicTitleByKey(ctx.database, comicCode, ulid);

	if (!result) throw new NotFoundError('comic title does not exist');
}

export async function listComicTitle(
	ctx: model.Context,
	param: model.ParameterComicTitle
): Promise<model.ComicTitle[]> {
	model.validateParameterComicTitle(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.selectComicTitle(ctx.database, param);
}

export async function countComicTitle(
	ctx: model.Context,
	param: model.ParameterComicTitle
): Promise<number> {
	model.validateParameterComicTitle(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.countComicTitle(ctx.database, param);
}

// Comic Cover

export async function addComicCover(
	ctx: model.Context,
	data: model.NewComicCover
): Promise<model.ComicCover> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICCOVER'])) {
		throw new AuthError('permission denied to add comic cover', AuthErrorType.Unauthorized);
	}

	return await database.insertComicCover(ctx.database, data);
}

export async function getComicCoverByKey(
	ctx: model.Context,
	comicCode: string,
	ulid: ULID
): Promise<model.ComicCover> {
	model.validateComicCoverKey(comicCode, ulid);

	const result = await database.selectComicCoverByKey(ctx.database, comicCode, ulid);

	if (!result) throw new NotFoundError('comic cover does not exist');

	return result;
}

export async function updateComicCoverByKey(
	ctx: model.Context,
	comicCode: string,
	ulid: ULID,
	data: model.SetComicCover
): Promise<model.ComicCover> {
	model.validateComicCoverKey(comicCode, ulid);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICCOVER'])) {
		throw new AuthError('permission denied to update comic cover', AuthErrorType.Unauthorized);
	}

	const result = await database.updateComicCoverByKey(ctx.database, comicCode, ulid, data);

	if (!result) throw new NotFoundError('comic cover does not exist');

	return result;
}

export async function deleteComicCoverByKey(
	ctx: model.Context,
	comicCode: string,
	ulid: ULID
): Promise<void> {
	model.validateComicCoverKey(comicCode, ulid);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICCOVER'])) {
		throw new AuthError('permission denied to delete comic cover', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteComicCoverByKey(ctx.database, comicCode, ulid);

	if (!result) throw new NotFoundError('comic cover does not exist');
}

export async function listComicCover(
	ctx: model.Context,
	param: model.ParameterComicCover
): Promise<model.ComicCover[]> {
	model.validateParameterComicCover(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.selectComicCover(ctx.database, param);
}

export async function countComicCover(
	ctx: model.Context,
	param: model.ParameterComicCover
): Promise<number> {
	model.validateParameterComicCover(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.countComicCover(ctx.database, param);
}

// Comic Synopsis

export async function addComicSynopsis(
	ctx: model.Context,
	data: model.NewComicSynopsis
): Promise<model.ComicSynopsis> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICSYNOPSIS'])) {
		throw new AuthError('permission denied to add comic synopsis', AuthErrorType.Unauthorized);
	}

	return await database.insertComicSynopsis(ctx.database, data);
}

export async function getComicSynopsisByKey(
	ctx: model.Context,
	comicCode: string,
	ulid: ULID
): Promise<model.ComicSynopsis> {
	model.validateComicSynopsisKey(comicCode, ulid);

	const result = await database.selectComicSynopsisByKey(ctx.database, comicCode, ulid);

	if (!result) throw new NotFoundError('comic synopsis does not exist');

	return result;
}

export async function updateComicSynopsisByKey(
	ctx: model.Context,
	comicCode: string,
	ulid: ULID,
	data: model.SetComicSynopsis
): Promise<model.ComicSynopsis> {
	model.validateComicSynopsisKey(comicCode, ulid);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICSYNOPSIS'])) {
		throw new AuthError('permission denied to update comic synopsis', AuthErrorType.Unauthorized);
	}

	const result = await database.updateComicSynopsisByKey(ctx.database, comicCode, ulid, data);

	if (!result) throw new NotFoundError('comic synopsis does not exist');

	return result;
}

export async function deleteComicSynopsisByKey(
	ctx: model.Context,
	comicCode: string,
	ulid: ULID
): Promise<void> {
	model.validateComicSynopsisKey(comicCode, ulid);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICSYNOPSIS'])) {
		throw new AuthError('permission denied to delete comic synopsis', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteComicSynopsisByKey(ctx.database, comicCode, ulid);

	if (!result) throw new NotFoundError('comic synopsis does not exist');
}

export async function listComicSynopsis(
	ctx: model.Context,
	param: model.ParameterComicSynopsis
): Promise<model.ComicSynopsis[]> {
	model.validateParameterComicSynopsis(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.selectComicSynopsis(ctx.database, param);
}

export async function countComicSynopsis(
	ctx: model.Context,
	param: model.ParameterComicSynopsis
): Promise<number> {
	model.validateParameterComicSynopsis(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.countComicSynopsis(ctx.database, param);
}

// Comic Character

export async function addComicCharacter(
	ctx: model.Context,
	data: model.NewComicCharacter
): Promise<model.ComicCharacter> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICCHARACTER'])) {
		throw new AuthError('permission denied to add comic character', AuthErrorType.Unauthorized);
	}

	return await database.insertComicCharacter(ctx.database, data);
}

export async function getComicCharacterByKey(
	ctx: model.Context,
	comicCode: string,
	characterCode: string
): Promise<model.ComicCharacter> {
	model.validateComicCharacterKey(comicCode, characterCode);

	const result = await database.selectComicCharacterByKey(ctx.database, comicCode, characterCode);

	if (!result) throw new NotFoundError('comic character does not exist');

	return result;
}

export async function updateComicCharacterByKey(
	ctx: model.Context,
	comicCode: string,
	characterCode: string,
	data: model.SetComicCharacter
): Promise<model.ComicCharacter> {
	model.validateComicCharacterKey(comicCode, characterCode);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICCHARACTER'])) {
		throw new AuthError('permission denied to update comic character', AuthErrorType.Unauthorized);
	}

	const result = await database.updateComicCharacterByKey(
		ctx.database,
		comicCode,
		characterCode,
		data
	);

	if (!result) throw new NotFoundError('comic character does not exist');

	return result;
}

export async function deleteComicCharacterByKey(
	ctx: model.Context,
	comicCode: string,
	characterCode: string
): Promise<void> {
	model.validateComicSynopsisKey(comicCode, characterCode);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICCHARACTER'])) {
		throw new AuthError('permission denied to delete comic character', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteComicCharacterByKey(ctx.database, comicCode, characterCode);

	if (!result) throw new NotFoundError('comic character does not exist');
}

export async function listComicCharacter(
	ctx: model.Context,
	param: model.ParameterComicCharacter
): Promise<model.ComicCharacter[]> {
	model.validateParameterComicSynopsis(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.selectComicCharacter(ctx.database, param);
}

export async function countComicCharacter(
	ctx: model.Context,
	param: model.ParameterComicCharacter
): Promise<number> {
	model.validateParameterComicSynopsis(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.countComicCharacter(ctx.database, param);
}

// Comic Author

export async function addComicAuthor(
	ctx: model.Context,
	data: model.NewComicAuthor
): Promise<model.ComicAuthor> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICAUTHOR'])) {
		throw new AuthError('permission denied to add comic author', AuthErrorType.Unauthorized);
	}

	return await database.insertComicAuthor(ctx.database, data);
}

export async function getComicAuthorByKey(
	ctx: model.Context,
	comicCode: string,
	positionCode: string,
	personCode: string
): Promise<model.ComicAuthor> {
	model.validateComicAuthorKey(comicCode, positionCode, personCode);

	const result = await database.selectComicAuthorByKey(
		ctx.database,
		comicCode,
		positionCode,
		personCode
	);

	if (!result) throw new NotFoundError('comic author does not exist');

	return result;
}

export async function updateComicAuthorByKey(
	ctx: model.Context,
	comicCode: string,
	positionCode: string,
	personCode: string,
	data: model.SetComicAuthor
): Promise<model.ComicAuthor> {
	model.validateComicAuthorKey(comicCode, positionCode, personCode);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICAUTHOR'])) {
		throw new AuthError('permission denied to update comic author', AuthErrorType.Unauthorized);
	}

	const result = await database.updateComicAuthorByKey(
		ctx.database,
		comicCode,
		positionCode,
		personCode,
		data
	);

	if (!result) throw new NotFoundError('comic author does not exist');

	return result;
}

export async function deleteComicAuthorByKey(
	ctx: model.Context,
	comicCode: string,
	positionCode: string,
	personCode: string
): Promise<void> {
	model.validateComicAuthorKey(comicCode, positionCode, personCode);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICAUTHOR'])) {
		throw new AuthError('permission denied to delete comic author', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteComicAuthorByKey(
		ctx.database,
		comicCode,
		positionCode,
		personCode
	);

	if (!result) throw new NotFoundError('comic author does not exist');
}

export async function listComicAuthor(
	ctx: model.Context,
	param: model.ParameterComicAuthor
): Promise<model.ComicAuthor[]> {
	model.validateParameterComicAuthor(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.selectComicAuthor(ctx.database, param);
}

export async function countComicAuthor(
	ctx: model.Context,
	param: model.ParameterComicAuthor
): Promise<number> {
	model.validateParameterComicAuthor(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.countComicAuthor(ctx.database, param);
}

// Comic Serialization

export async function addComicSerialization(
	ctx: model.Context,
	data: model.NewComicSerialization
): Promise<model.ComicSerialization> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICSERIALIZATION'])) {
		throw new AuthError('permission denied to add comic serialization', AuthErrorType.Unauthorized);
	}

	return await database.insertComicSerialization(ctx.database, data);
}

export async function getComicSerializationByKey(
	ctx: model.Context,
	comicCode: string,
	magazineCode: string
): Promise<model.ComicSerialization> {
	model.validateComicSerializationKey(comicCode, magazineCode);

	const result = await database.selectComicSerializationByKey(
		ctx.database,
		comicCode,
		magazineCode
	);

	if (!result) throw new NotFoundError('comic serialization does not exist');

	return result;
}

export async function updateComicSerializationByKey(
	ctx: model.Context,
	comicCode: string,
	magazineCode: string,
	data: model.SetComicSerialization
): Promise<model.ComicSerialization> {
	model.validateComicSerializationKey(comicCode, magazineCode);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICSERIALIZATION'])) {
		throw new AuthError(
			'permission denied to update comic serialization',
			AuthErrorType.Unauthorized
		);
	}

	const result = await database.updateComicSerializationByKey(
		ctx.database,
		comicCode,
		magazineCode,
		data
	);

	if (!result) throw new NotFoundError('comic serialization does not exist');

	return result;
}

export async function deleteComicSerializationByKey(
	ctx: model.Context,
	comicCode: string,
	magazineCode: string
): Promise<void> {
	model.validateComicSerializationKey(comicCode, magazineCode);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICSERIALIZATION'])) {
		throw new AuthError(
			'permission denied to delete comic serialization',
			AuthErrorType.Unauthorized
		);
	}

	const result = await database.deleteComicSerializationByKey(
		ctx.database,
		comicCode,
		magazineCode
	);

	if (!result) throw new NotFoundError('comic serialization does not exist');
}

export async function listComicSerialization(
	ctx: model.Context,
	param: model.ParameterComicSerialization
): Promise<model.ComicSerialization[]> {
	model.validateParameterComicSerialization(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.selectComicSerialization(ctx.database, param);
}

export async function countComicSerialization(
	ctx: model.Context,
	param: model.ParameterComicSerialization
): Promise<number> {
	model.validateParameterComicSerialization(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.countComicSerialization(ctx.database, param);
}

// Comic External

export async function addComicExternal(
	ctx: model.Context,
	data: model.NewComicExternal
): Promise<model.ComicExternal> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICEXTERNAL'])) {
		throw new AuthError('permission denied to add comic external', AuthErrorType.Unauthorized);
	}

	return await database.insertComicExternal(ctx.database, data);
}

export async function getComicExternalByKey(
	ctx: model.Context,
	comicCode: string,
	linkWebsiteHost: string,
	linkRelativeReference: string
): Promise<model.ComicExternal> {
	model.validateComicExternalKey(comicCode, linkWebsiteHost, linkRelativeReference);

	const result = await database.selectComicExternalByKey(
		ctx.database,
		comicCode,
		linkWebsiteHost,
		linkRelativeReference
	);

	if (!result) throw new NotFoundError('comic external does not exist');

	return result;
}

export async function updateComicExternalByKey(
	ctx: model.Context,
	comicCode: string,
	linkWebsiteHost: string,
	linkRelativeReference: string,
	data: model.SetComicExternal
): Promise<model.ComicExternal> {
	model.validateComicExternalKey(comicCode, linkWebsiteHost, linkRelativeReference);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICEXTERNAL'])) {
		throw new AuthError('permission denied to update comic external', AuthErrorType.Unauthorized);
	}

	const result = await database.updateComicExternalByKey(
		ctx.database,
		comicCode,
		linkWebsiteHost,
		linkRelativeReference,
		data
	);

	if (!result) throw new NotFoundError('comic external does not exist');

	return result;
}

export async function deleteComicExternalByKey(
	ctx: model.Context,
	comicCode: string,
	linkWebsiteHost: string,
	linkRelativeReference: string
): Promise<void> {
	model.validateComicExternalKey(comicCode, linkWebsiteHost, linkRelativeReference);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICEXTERNAL'])) {
		throw new AuthError('permission denied to delete comic external', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteComicExternalByKey(
		ctx.database,
		comicCode,
		linkWebsiteHost,
		linkRelativeReference
	);

	if (!result) throw new NotFoundError('comic external does not exist');
}

export async function listComicExternal(
	ctx: model.Context,
	param: model.ParameterComicExternal
): Promise<model.ComicExternal[]> {
	model.validateParameterComicExternal(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.selectComicExternal(ctx.database, param);
}

export async function countComicExternal(
	ctx: model.Context,
	param: model.ParameterComicExternal
): Promise<number> {
	model.validateParameterComicExternal(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.countComicExternal(ctx.database, param);
}

// Comic Chapter

export async function addComicChapter(
	ctx: model.Context,
	data: model.NewComicChapter
): Promise<model.ComicChapter> {
	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICCHAPTER'])) {
		throw new AuthError('permission denied to add comic chapter', AuthErrorType.Unauthorized);
	}

	return await database.insertComicChapter(ctx.database, data);
}

export async function getComicChapterByKey(
	ctx: model.Context,
	comicCode: string,
	number: number,
	version: string | null
): Promise<model.ComicChapter> {
	model.validateComicChapterKey(comicCode, number, version);

	const result = await database.selectComicChapterByKey(ctx.database, comicCode, number, version);

	if (!result) throw new NotFoundError('comic chapter does not exist');

	return result;
}

export async function updateComicChapterByKey(
	ctx: model.Context,
	comicCode: string,
	number: number,
	version: string | null,
	data: model.SetComicChapter
): Promise<model.ComicChapter> {
	model.validateComicChapterKey(comicCode, number, version);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICCHAPTER'])) {
		throw new AuthError('permission denied to update comic chapter', AuthErrorType.Unauthorized);
	}

	const result = await database.updateComicChapterByKey(
		ctx.database,
		comicCode,
		number,
		version,
		data
	);

	if (!result) throw new NotFoundError('comic chapter does not exist');

	return result;
}

export async function deleteComicChapterByKey(
	ctx: model.Context,
	comicCode: string,
	number: number,
	version: string | null
): Promise<void> {
	model.validateComicChapterKey(comicCode, number, version);

	if (!ctx.user || !ctx.user.hasPermission(['WRITE', 'COMICCHAPTER'])) {
		throw new AuthError('permission denied to delete comic chapter', AuthErrorType.Unauthorized);
	}

	const result = await database.deleteComicChapterByKey(ctx.database, comicCode, number, version);

	if (!result) throw new NotFoundError('comic chapter does not exist');
}

export async function listComicChapter(
	ctx: model.Context,
	param: model.ParameterComicChapter
): Promise<model.ComicChapter[]> {
	model.validateParameterComicChapter(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.selectComicChapter(ctx.database, param).then(async (result) => {
		return await Promise.all(
			result.map(async (result) => {
				result.titleCount = -1;

				return result;
			})
		);
	});
}

export async function countComicChapter(
	ctx: model.Context,
	param: model.ParameterComicChapter
): Promise<number> {
	model.validateParameterComicChapter(param);

	if (param.criteria) {
		if (isStringArray(param.criteria['comicCodes'])) {
			param.criteriaComicCodes = param.criteria['comicCodes'];
		}
	}

	return await database.countComicChapter(ctx.database, param);
}
