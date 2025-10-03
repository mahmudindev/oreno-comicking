export class HREF {
    #components: Record<string, string> = {};

    constructor(href: string) {
        if (href == '') {
            return;
        }

        const separateProtocol = href.split('://', 2);

        let newHREF = separateProtocol[1] ?? separateProtocol[0];

        if (newHREF.startsWith('//')) {
            newHREF = newHREF.substring(2);
        }

        if (newHREF.startsWith('?') || newHREF.startsWith('#')) {
            this.#components['relativeReference'] = newHREF;
            return;
        }

        const separateHostInfo = newHREF.split('/', 2);

        let host = separateHostInfo[0];
        if (host.includes('@')) {
            host = host.split('@', 2)[1];
        }

        const separatePort = host.split(':', 2);

        this.#components['host'] = separatePort[0];

        if (separateHostInfo[1] != undefined) {
            this.#components['relativeReference'] = '/' + separateHostInfo[1];
        }
    }

    public getHost(): string | null {
        return this.#components['host'] ?? null;
    }

    public getRelativeReference(): string | null {
        return this.#components['relativeReference'] ?? null;
    }
}
