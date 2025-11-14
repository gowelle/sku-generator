# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.2.x   | :white_check_mark: |
| 1.1.x   | :white_check_mark: |
| 1.0.x   | :warning:          |
| < 1.0   | :x:                |

## Reporting a Vulnerability

We take the security of this package seriously. If you discover any security-related issues, please email security@gowelle.com instead of using the issue tracker.

Please include:

1. Description of the vulnerability
2. Steps to reproduce the issue
3. Possible impact of the vulnerability
4. Suggested fix (if any)

You can expect:

- A confirmation email within 24 hours
- Regular updates about our progress
- Credit in the security advisory (unless you prefer to remain anonymous)

## Security Updates

Security updates will be released as soon as possible, typically within a few days of discovery. They will be published as:

1. A new release with a security fix
2. A security advisory in the GitHub Security tab
3. A notification in the release notes

## Best Practices

When using the SKU Generator package:

- **Always use the latest version** - v1.2.x or higher includes audit trail and compliance features
- **Keep your Laravel installation up to date** - The package supports Laravel 10, 11, and 12
- **Configure audit trail retention** - Use the `retention_days` setting to manage SKU history storage
- **Enable user/IP tracking** - Configure `track_user_id`, `track_ip_address`, and `track_user_agent` in the package config for compliance
- **Control SKU regeneration access** - Restrict access to the `sku:regenerate` Artisan command to authorized administrators only
- **Monitor audit logs** - Regularly review the `sku_history` table for unusual SKU generation patterns
- **Follow Laravel's security best practices** - Keep dependencies updated, validate inputs, and use proper access controls
- **Implement proper authorization** - Use Laravel's authorization gates/policies to control who can trigger SKU regeneration
- **Monitor event dispatches** - The package dispatches events that you can listen to for custom logging or alerting

## Security Features

Starting with v1.2.0, the package includes comprehensive audit trail and compliance features:

- **Complete SKU History Tracking** - All SKU creations, updates, and regenerations are logged with timestamps
- **Optional User Tracking** - Track which user triggered each SKU change (requires `track_user_id` configuration)
- **IP Address Logging** - Optionally log the IP address of the client making SKU generation requests
- **User-Agent Tracking** - Optionally capture the user agent for additional audit context
- **Event Dispatching** - Custom events are dispatched for SKU operations, enabling external monitoring and alerting
- **Data Retention Policies** - Configure how long history records are retained for compliance with data retention laws

These features support compliance requirements such as SOC 2, GDPR, and other regulatory standards requiring audit trails.

## Disclosure Policy

1. Security issues are reported privately
2. Issues are confirmed and fixed
3. Package is patched and released
4. Public disclosure after users have had time to update

## Contact

- Email: security@gowelle.com