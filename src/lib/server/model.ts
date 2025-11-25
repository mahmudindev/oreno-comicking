import type { DB } from './database';
import { User } from './user';
import * as model from '$lib/model';

export interface Context extends model.Context {
	user: User | null;
	database: DB;
}

export interface Language extends model.Language {
	id: number;
}

export interface Website extends model.Website {
	id: number;
}

export interface Link extends model.Link {
	id: number;
}

export interface ParameterLink extends model.ParameterLink {
	criteriaWebsiteIDs?: number[];
}

export interface Character extends model.Character {
	id: number;
}

export interface Person extends model.Person {
	id: number;
}

export interface Magazine extends model.Magazine {
	id: number;
}

export interface Category extends model.Category {
	id: number;
}

export interface ParameterCategory extends model.ParameterCategory {
	criteriaTypeIDs?: number[];
	criteriaIDs?: number[];
	criteriaParentIDs?: number[];
}

export interface CategoryType extends model.CategoryType {
	id: number;
}

export interface Tag extends model.Tag {
	id: number;
}

export interface ParameterTag extends model.ParameterTag {
	criteriaTypeIDs?: number[];
	criteriaIDs?: number[];
}

export interface TagType extends model.TagType {
	id: number;
}

export interface Comic extends model.Comic {
	id: number;
}

export interface ComicTitle extends model.ComicTitle {
	id: number;
}

export interface ParameterComicTitle extends model.ParameterComicTitle {
	criteriaComicIDs?: number[];
}

export interface ComicCover extends model.ComicCover {
	id: number;
}

export interface ParameterComicCover extends model.ParameterComicCover {
	criteriaComicIDs?: number[];
}

export interface ComicSynopsis extends model.ComicSynopsis {
	id: number;
}

export interface ParameterComicSynopsis extends model.ParameterComicSynopsis {
	criteriaComicIDs?: number[];
}

export interface ComicCharacter extends model.ComicCharacter {
	id: number;
}

export interface ParameterComicCharacter extends model.ParameterComicCharacter {
	criteriaComicIDs?: number[];
}

export interface ComicAuthor extends model.ComicAuthor {
	id: number;
}

export interface ParameterComicAuthor extends model.ParameterComicAuthor {
	criteriaComicIDs?: number[];
}

export interface ComicSerialization extends model.ComicSerialization {
	id: number;
}

export interface ParameterComicSerialization extends model.ParameterComicSerialization {
	criteriaComicIDs?: number[];
}

export interface ComicExternal extends model.ComicExternal {
	id: number;
}

export interface ParameterComicExternal extends model.ParameterComicExternal {
	criteriaComicIDs?: number[];
}

export interface ComicChapter extends model.ComicChapter {
	id: number;
}

export interface ParameterComicChapter extends model.ParameterComicChapter {
	criteriaComicIDs?: number[];
}

export interface ComicAuthorPosition extends model.ComicAuthorPosition {
	id: number;
}

export * from '$lib/model';
