/**
 * GSC property URL utilities.
 *
 * Google Search Console properties come in two formats from the API:
 *   - Domain properties:     "sc-domain:example.com"
 *   - URL-prefix properties: "https://www.example.com/"
 *
 * Raw property_url values are stored as-is in the DB and used as-is for API
 * calls — only format them for display purposes using these helpers.
 *
 * Related: app/Models/SearchConsoleProperty.php
 * Related: app/Jobs/SyncSearchConsoleJob.php
 */

/** Returns a clean, human-readable label for a GSC property URL. */
export function formatGscProperty(propertyUrl: string): string {
    if (propertyUrl.startsWith('sc-domain:')) {
        return propertyUrl.slice('sc-domain:'.length);
    }
    return propertyUrl.replace(/^https?:\/\//, '').replace(/\/$/, '');
}

/** Returns the property type based on its URL format. */
export function getGscPropertyType(propertyUrl: string): 'domain' | 'url_prefix' {
    return propertyUrl.startsWith('sc-domain:') ? 'domain' : 'url_prefix';
}
