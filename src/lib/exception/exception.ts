//
// General
//

export function appendErrorMessage(e: Error, s: string) {
	e.message = s + ' ' + e.message;
}

export class GenericError extends Error {
	constructor(message?: string, options?: ErrorOptions) {
		super(message, options);
		this.name = GenericError.name;
	}
}

export class NotFoundError extends GenericError {
	constructor(message?: string, options?: ErrorOptions) {
		super(message, options);
		this.name = NotFoundError.name;
	}
}

export class ValidationError extends Error {
	constructor(message?: string, options?: ErrorOptions) {
		super(message, options);
		this.name = ValidationError.name;
	}
}

//
// Database
//

export class DatabaseError extends GenericError {
	constructor(message?: string, options?: ErrorOptions) {
		super(message, options);
		this.name = DatabaseError.name;
	}
}

//
// API
//

export class APIError extends GenericError {
	constructor(message?: string, options?: ErrorOptions) {
		super(message, options);
		this.name = APIError.name;
	}
}

//
// Auth
//

export enum AuthErrorType {
	Unknown,
	Unauthorized,
	Expired
}

export class AuthError extends GenericError {
	#type: AuthErrorType;

	constructor(
		message?: string,
		type: AuthErrorType = AuthErrorType.Unknown,
		options?: ErrorOptions
	) {
		super(message, options);
		this.#type = type;
		this.name = AuthError.name;
	}

	public getType(): AuthErrorType {
		return this.#type;
	}
}
