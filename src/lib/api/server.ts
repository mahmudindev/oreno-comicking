import type { components as Components } from './openapi';

export type APIError = Components['schemas']['Error'];

export const Error415JSON: APIError = {
	message: 'unsupported media type'
};

export const Error500JSON: APIError = {
	message: 'internal server error'
};
