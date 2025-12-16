# Database Setup Guide

## Quick Start

### For Fresh Installation
Run `setup_complete.sql` - This creates everything from scratch:
```bash
mysql -u your_username -p < database/setup_complete.sql
```

### For Existing Database
Run `update_existing_database.sql` - This safely updates your existing database:
```bash
mysql -u your_username -p < database/update_existing_database.sql
```

### Verify Your Database
Run `verify_schema.sql` to check if everything is set up correctly:
```bash
mysql -u your_username -p < database/verify_schema.sql
```

## SQL Files Overview

### Main Files
- **`schema.sql`** - Main schema file (includes all tables)
- **`setup_complete.sql`** - Complete setup for fresh installations (recommended)

### Migration Files (for existing databases)
- **`update_existing_database.sql`** - All-in-one update script (recommended for updates)
- **`migration_add_location_type.sql`** - Adds location_type column
- **`migration_ponding_to_water.sql`** - Updates condition_type from ponding_flooding to water
- **`schema_votes_comments.sql`** - Adds votes and comments tables (already in main schema)

### Utility Files
- **`verify_schema.sql`** - Verifies database structure

## Database Structure

### Tables

#### `reports`
- Stores road condition reports
- **condition_type**: ENUM('ice', 'slush', 'snow', 'water')
- **location_type**: ENUM('road', 'sidewalk')
- Only shows reports from last 24 hours

#### `votes`
- Stores up/down votes on reports
- One vote per IP per report (prevents spam)
- Foreign key to reports (cascades on delete)

#### `comments`
- Stores anonymous comments on reports
- Foreign key to reports (cascades on delete)

## Current Schema Status

✅ All tables properly defined
✅ All foreign keys configured
✅ All indexes created
✅ Condition types: ice, slush, snow, water
✅ Location types: road, sidewalk
✅ UTF8MB4 encoding for emoji support

## Notes

- The `update_existing_database.sql` script is safe to run multiple times
- All migration scripts check for existing columns/tables before creating
- Foreign keys use CASCADE delete (deleting a report deletes its votes/comments)






