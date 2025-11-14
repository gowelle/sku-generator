# SKU Generator - Product Roadmap

## Current Version: 1.2.0

## Latest Feature: SKU History & Audit Trail (v1.2.0) ✅

### Executive Summary
Implement a comprehensive audit trail system to track all SKU lifecycle events, providing transparency, compliance support, and debugging capabilities for production e-commerce systems.

### Business Value
- **Compliance**: Meet regulatory requirements for inventory tracking and change management
- **Debugging**: Quickly identify when and why SKU changes occurred
- **Transparency**: Full visibility into SKU operations for stakeholders
- **Data Integrity**: Prevent and resolve SKU conflicts with historical context
- **Reporting**: Analytics on SKU generation patterns and usage

### Feature Specifications

#### 1. SKU History Model
Create a new `SkuHistory` model to track:
- Original SKU value
- New SKU value (null for deletions)
- Model type and ID
- Event type (created, regenerated, modified, deleted)
- User/actor responsible (if authenticated)
- Timestamp
- Additional metadata (reason, IP address, user agent)
- Change context (manual, automatic, migration)

**Database Schema:**
```php
Schema::create('sku_histories', function (Blueprint $table) {
    $table->id();
    $table->string('old_sku')->nullable()->index();
    $table->string('new_sku')->nullable()->index();
    $table->string('model_type')->index();
    $table->unsignedBigInteger('model_id')->index();
    $table->string('event_type'); // created, regenerated, modified, deleted
    $table->unsignedBigInteger('user_id')->nullable();
    $table->string('user_type')->nullable(); // For polymorphic user support
    $table->text('metadata')->nullable(); // JSON field for additional context
    $table->string('reason')->nullable();
    $table->ipAddress('ip_address')->nullable();
    $table->string('user_agent')->nullable();
    $table->timestamps();

    $table->index(['model_type', 'model_id']);
    $table->index('created_at');
});
```

#### 2. Automatic History Logging
Integrate history logging into existing SKU operations:
- **Creation**: Log when SKU is first generated
- **Regeneration**: Track when `forceRegenerateSku()` is called
- **Modification**: Detect and log manual SKU changes (if allowed)
- **Deletion**: Record when models with SKUs are deleted (soft or hard)

#### 3. Configuration Options
Add new configuration settings:

```php
'history' => [
    'enabled' => env('SKU_HISTORY_ENABLED', true),
    'track_user' => true,
    'track_ip' => false,
    'track_user_agent' => false,
    'retention_days' => null, // null = keep forever, int = days to retain
    'table_name' => 'sku_histories',
],
```

#### 4. Query Interface
Provide convenient methods to query SKU history:

```php
// On models with HasSku trait
$product->skuHistory(); // Returns history collection
$product->skuHistory()->latest(); // Most recent change
$product->skuHistory()->byUser($userId); // Changes by specific user
$product->skuHistory()->between($startDate, $endDate); // Date range

// Global queries
SkuHistory::forModel($product);
SkuHistory::forSku('TM-TSH-ABC12345');
SkuHistory::byEventType('regenerated');
SkuHistory::recentChanges($days = 7);
```

#### 5. Artisan Commands

**View History:**
```bash
php artisan sku:history "App\Models\Product" --id=123
php artisan sku:history --sku="TM-TSH-ABC12345"
php artisan sku:history --recent --days=7
```

**Cleanup Old History:**
```bash
php artisan sku:history:cleanup --days=365
php artisan sku:history:cleanup --before="2024-01-01"
```

#### 6. Events & Listeners
Dispatch Laravel events for integration:
- `SkuCreated`
- `SkuRegenerated`
- `SkuModified`
- `SkuDeleted`

Allow external systems to react to SKU changes.

#### 7. Relationship Tracking
Track relationships in history:
- Parent product SKU changes affecting variants
- Bulk operations (mass regeneration)
- Cascade effects

### Technical Implementation

#### Phase 1: Core Infrastructure ✅
- [x] Create migration for `sku_histories` table
- [x] Create `SkuHistory` model with relationships
- [x] Add configuration options
- [x] Create events (SkuCreated, SkuRegenerated, SkuModified, SkuDeleted)

#### Phase 2: Integration ✅
- [x] Integrate logging into `HasSku` trait
- [x] Update `SkuGenerator` to emit events
- [x] Add history logging to regenerate command
- [x] Implement query scopes and helper methods

#### Phase 3: Commands & Tools ✅
- [x] Create `sku:history` command for viewing history
- [x] Create `sku:history:cleanup` command
- [x] Implement retention policy

#### Phase 4: Testing & Documentation ✅
- [x] Unit tests for history logging
- [x] Integration tests for all event types
- [x] Test retention and cleanup
- [x] Update README with history documentation
- [x] Add examples to documentation

### Backward Compatibility
- Feature is opt-in via configuration
- No breaking changes to existing API
- History logging can be disabled
- Migration is optional

### Performance Considerations
- Use database transactions for atomic operations
- Queue history writes for high-volume operations
- Add indexes for common queries
- Implement retention policy to prevent table bloat
- Consider partitioning for large datasets

### Future Enhancements (v1.2.1+)
- Dashboard/UI for viewing history
- Export history to CSV/JSON
- Restore previous SKU from history
- Compare SKU changes side-by-side
- Integration with Laravel Telescope

---

## Future Feature Ideas (v1.3.0+)

### Priority 1: High Value Features

#### Custom SKU Patterns (v1.3.0)
Allow users to define custom SKU formats:
```php
'pattern' => '{prefix}-{category:3}-{year:2}-{ulid:8}-{suffix}',
'pattern_variant' => '{parent_sku}-{properties}',
```

**Benefits:**
- Flexibility for different business requirements
- Support for date-based SKUs
- Custom separators and formatting
- Conditional segments

#### SKU Preview & Validation (v1.3.0)
Preview SKUs before generation:
```php
SkuGenerator::preview($product); // Returns preview without saving
SkuGenerator::validate($sku); // Validates against business rules
```

**Benefits:**
- Prevent mistakes before committing
- Validate SKU format against custom rules
- Dry-run for bulk operations
- User feedback during data entry

### Priority 2: Integration Features

#### Laravel Events Integration (v1.4.0)
Comprehensive event system:
- Observable pattern for SKU lifecycle
- Webhook support for external systems
- Integration with Laravel Echo for real-time updates

#### Multi-tenant Support (v1.4.0)
Support for multi-tenant applications:
```php
'prefix' => function ($model) {
    return auth()->user()->tenant->sku_prefix;
},
```

**Features:**
- Tenant-specific prefixes
- Isolated SKU namespaces
- Tenant-aware uniqueness checks

### Priority 3: Advanced Features

#### Hierarchical Category Support (v1.5.0)
Support for nested categories:
```php
'category' => [
    'hierarchical' => true,
    'separator' => '.',
    'max_depth' => 3,
],
```

Example: `TM-CLO.SHR.TSH-ABC12345` (Clothing > Shirts > T-Shirts)

#### Soft Delete Awareness (v1.5.0)
Proper handling of soft-deleted models:
- Prevent SKU reuse when soft-deleted
- Option to release SKUs after permanent deletion
- Restore SKUs when undeleting

#### Bulk Operations API (v1.6.0)
Advanced bulk operations:
```bash
php artisan sku:transform --from-pattern="{old}" --to-pattern="{new}"
php artisan sku:merge --source="OLD-SKU" --target="NEW-SKU"
php artisan sku:export --format=csv
php artisan sku:import --file=skus.csv
```

### Priority 4: Analytics & Reporting

#### SKU Analytics Dashboard (v1.7.0)
Built-in analytics:
- SKU generation trends
- Category distribution
- Conflict resolution statistics
- Most regenerated SKUs
- Orphaned SKU detection

#### REST API Endpoints (v1.7.0)
Expose SKU operations via API:
```php
POST /api/sku/generate
POST /api/sku/validate
GET /api/sku/{sku}/history
POST /api/sku/{id}/regenerate
```

### Priority 5: Developer Experience

#### SKU Templates (v1.8.0)
Predefined templates for common scenarios:
```php
'templates' => [
    'fashion' => ['pattern' => '{prefix}-{category}-{season}-{ulid}'],
    'electronics' => ['pattern' => '{prefix}-{brand}-{category}-{model}'],
],
```

#### Testing Utilities (v1.8.0)
Helper factories for testing:
```php
SkuFactory::make('product'); // Generate test SKU
SkuFactory::makeUnique(10); // Generate 10 unique SKUs
SkuFactory::makePattern('{pattern}'); // Custom pattern
```

---

## Release Schedule

- **v1.2.0** (Released): ✅ SKU History & Audit Trail
- **v1.3.0** (Q2 2026): Custom Patterns & Preview
- **v1.4.0** (Q3 2026): Events & Multi-tenant Support
- **v1.5.0** (Q4 2026): Advanced Category & Soft Delete
- **v1.6.0** (Q1 2027): Bulk Operations
- **v1.7.0** (Q2 2027): Analytics & REST API
- **v1.8.0** (Q3 2027): Templates & Testing Utilities

---

## Community Feedback

We welcome feedback on this roadmap. Please open an issue on GitHub to:
- Suggest new features
- Prioritize existing features
- Share your use cases
- Report pain points

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to these features.

---

**Last Updated:** 2025-11-14
**Current Version:** 1.2.0
**Latest Release:** v1.2.0 (SKU History & Audit Trail) ✅
**Next Release:** v1.3.0 (Custom Patterns & Preview)
