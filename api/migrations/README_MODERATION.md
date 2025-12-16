# Comment Moderation Setup

This document explains how to set up OpenAI moderation for comments.

## Prerequisites

1. An OpenAI API key (get one from https://platform.openai.com/api-keys)
2. Access to your database to run the migration

## Setup Steps

### 1. Run Database Migration

Run the SQL migration script to add the `status` column to the comments table:

```sql
-- Run this in your MySQL database
ALTER TABLE `slippy_comments` 
ADD COLUMN `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' AFTER `comment_text`;

CREATE INDEX `idx_status` ON `slippy_comments` (`status`);
```

Or use the migration file: `api/migrations/add_comment_status_column.sql`

**Note:** If you have existing comments, you may want to approve them:
```sql
UPDATE `slippy_comments` SET status = 'approved' WHERE status = 'pending';
```

### 2. Configure OpenAI API Key

You have two options to set your OpenAI API key:

#### Option A: Environment Variable (Recommended)
Set the `OPENAI_API_KEY` environment variable on your server:
```bash
export OPENAI_API_KEY="your-api-key-here"
```

#### Option B: Direct Configuration
Edit `config/database.php` and uncomment/modify this line:
```php
define('OPENAI_API_KEY', 'your-api-key-here');
```

### 3. Verify Cron Job is Running

The moderation cron job runs every 2 minutes. To verify it's scheduled:

1. Check WordPress cron: Visit your site's `wp-cron.php?do=slippy_moderate_comments` (requires admin access)
2. Check error logs: The cron logs activity to WordPress error logs

### 4. Test the System

1. Submit a test comment
2. You should see "Comment has been submitted and is pending moderation"
3. Wait up to 2 minutes for the cron to process it
4. Refresh the page - approved comments will appear, rejected ones won't

## How It Works

1. **Comment Submission**: When a user submits a comment, it's saved with `status = 'pending'`
2. **User Feedback**: User sees "Comment has been submitted" message (comment doesn't appear immediately)
3. **Background Processing**: WordPress cron job runs every 2 minutes and:
   - Fetches up to 10 pending comments
   - Sends each to OpenAI Moderation API
   - Updates status to 'approved' (if safe) or 'rejected' (if flagged)
4. **Display**: Only comments with `status = 'approved'` are shown to users

## Monitoring

Check your WordPress error logs for moderation activity:
- `Slippy moderation cron: Processed X comments, approved Y, rejected Z, errors W`
- Rejected comments will log the flagged categories

## Troubleshooting

- **Comments not appearing**: Check that the cron job is running and OpenAI API key is configured
- **API errors**: Check error logs for OpenAI API issues (rate limits, invalid key, etc.)
- **All comments pending**: Verify the cron job is scheduled and running

