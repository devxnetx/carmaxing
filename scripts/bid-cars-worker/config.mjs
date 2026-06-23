import { readFileSync, existsSync } from 'fs';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

const workerDir = dirname(fileURLToPath(import.meta.url));

export const FULL_PAGES = 'full';
export const MAX_PAGES_PER_BRAND = 200;

const defaultFilters = {
    'search-type': 'filters',
    status: 'All',
    type: 'Automobile',
    model: 'All',
    'year-from': '2005',
    'year-to': '2027',
    'auction-type': 'All',
    'start-code': 'Run and Drive',
    transmission: 'Automatic',
    'engine-hp-from': '148',
    'estimated-min': '4500',
};

/**
 * @param {unknown} value
 * @param {number | 'full'} [fallback]
 * @returns {number | 'full'}
 */
export function parsePagesPerBrand(value, fallback = 3) {
    if (value === undefined || value === null || value === '') {
        return fallback;
    }

    const normalized = String(value).trim().toLowerCase();

    if (normalized === FULL_PAGES) {
        return FULL_PAGES;
    }

    const parsed = Number.parseInt(normalized, 10);

    if (Number.isNaN(parsed) || parsed <= 0) {
        return FULL_PAGES;
    }

    return Math.max(1, parsed);
}

const DEFAULT_IMPORT_API_PATH = '/api/v1/bid-cars/import';

export function normalizeBackendDomain(domain) {
    return String(domain ?? '').trim().replace(/\/$/, '');
}

export function buildImportApiUrl(domain) {
    const normalized = normalizeBackendDomain(domain);

    if (!normalized) {
        return '';
    }

    return `${normalized}${DEFAULT_IMPORT_API_PATH}`;
}

export function stripJsonComments(text) {
    let result = '';
    let inString = false;
    let escaped = false;

    for (let index = 0; index < text.length; index++) {
        const char = text[index];
        const next = text[index + 1];

        if (inString) {
            result += char;
            escaped = char === '\\' && !escaped;
            if (char === '"' && !escaped) {
                inString = false;
            }

            continue;
        }

        if (char === '"') {
            inString = true;
            result += char;
            continue;
        }

        if (char === '/' && next === '/') {
            while (index < text.length && text[index] !== '\n') {
                index++;
            }

            result += '\n';
            continue;
        }

        if (char === '/' && next === '*') {
            index += 2;

            while (index < text.length && !(text[index] === '*' && text[index + 1] === '/')) {
                index++;
            }

            index++;
            continue;
        }

        result += char;
    }

    return result;
}

function readJsonFile(path) {
    return JSON.parse(stripJsonComments(readFileSync(path, 'utf8')));
}

function loadRawImportConfig(importConfigPath) {
    const raw = readJsonFile(importConfigPath);
    const localPath = importConfigPath.replace(/\.json$/, '.local.json');

    if (!existsSync(localPath)) {
        return raw;
    }

    return {
        ...raw,
        ...readJsonFile(localPath),
    };
}

export function resolveApiUrl(raw, env = process.env) {
    if (raw.apiUrl) {
        return String(raw.apiUrl).trim().replace(/\/$/, '');
    }

    const domain = raw.backendDomain
        ?? raw.backend?.domain
        ?? env.BID_CARS_BACKEND_DOMAIN
        ?? '';

    return buildImportApiUrl(domain);
}

export function resolveApiKey(raw, env = process.env) {
    return String(
        env.BID_CARS_IMPORT_API_KEY
            ?? raw.apiKey
            ?? raw.backend?.apiKey
            ?? '',
    ).trim();
}

export function resolveImportConfigPath(localConfig = {}) {
    if (localConfig.importConfigPath) {
        return resolve(workerDir, String(localConfig.importConfigPath));
    }

    if (process.env.BID_CARS_IMPORT_CONFIG) {
        return resolve(process.env.BID_CARS_IMPORT_CONFIG);
    }

    return resolve(workerDir, 'import.config.json');
}

/**
 * @param {string} [importConfigPath]
 */
export function loadImportConfig(importConfigPath = resolve(workerDir, 'import.config.json')) {
    if (!existsSync(importConfigPath)) {
        throw new Error(`Missing import config: ${importConfigPath}`);
    }

    const raw = loadRawImportConfig(importConfigPath);

    return {
        backendDomain: normalizeBackendDomain(
            raw.backendDomain ?? raw.backend?.domain ?? process.env.BID_CARS_BACKEND_DOMAIN ?? '',
        ),
        apiKey: resolveApiKey(raw),
        apiUrl: resolveApiUrl(raw),
        brands: Array.isArray(raw.brands) && raw.brands.length > 0
            ? raw.brands.map((brand) => String(brand).trim()).filter(Boolean)
            : ['Audi', 'BMW', 'Mercedes-Benz'],
        pagesPerBrand: parsePagesPerBrand(raw.pagesPerBrand, 3),
        headless: raw.headless !== false,
        filters: {
            ...defaultFilters,
            ...(raw.filters ?? {}),
        },
        importConfigPath,
    };
}

/**
 * @param {Record<string, unknown>} localConfig
 */
export function loadWorkerConfig(localConfig) {
    const importSettings = loadImportConfig(resolveImportConfigPath(localConfig));

    const brands = Array.isArray(localConfig.brands) && localConfig.brands.length > 0
        ? localConfig.brands.map((brand) => String(brand).trim()).filter(Boolean)
        : importSettings.brands;

    const pagesPerBrand = localConfig.pagesPerBrand !== undefined
        ? parsePagesPerBrand(localConfig.pagesPerBrand, importSettings.pagesPerBrand)
        : importSettings.pagesPerBrand;

    const headlessSetting = localConfig.headless ?? importSettings.headless;

    const apiUrl = localConfig.apiUrl
        ? String(localConfig.apiUrl).trim().replace(/\/$/, '')
        : (localConfig.backendDomain
            ? buildImportApiUrl(localConfig.backendDomain)
            : importSettings.apiUrl);

    const apiKey = String(
        localConfig.apiKey
            ?? process.env.BID_CARS_IMPORT_API_KEY
            ?? importSettings.apiKey
            ?? '',
    ).trim();

    const backendDomain = normalizeBackendDomain(
        localConfig.backendDomain ?? importSettings.backendDomain,
    );

    return {
        backendDomain,
        apiUrl,
        apiKey,
        brands,
        pagesPerBrand,
        headless: headlessSetting !== false && process.env.BID_CARS_HEADLESS !== '0',
        filters: {
            ...importSettings.filters,
            ...(localConfig.filters ?? {}),
        },
        importConfigPath: importSettings.importConfigPath,
    };
}