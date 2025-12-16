# Autocomplete API Options

This document explains the different autocomplete API options available, ordered by speed.

## Current Implementation

The app now supports **Google Places Autocomplete API** (fastest) with automatic fallback to **Nominatim** (free but slower).

## API Options (Fastest to Slowest)

### 1. Google Places Autocomplete API ⚡ (FASTEST - Recommended)
- **Speed**: ~50-100ms response time
- **Cost**: Pay-as-you-go, $0.017 per 1000 requests (first $200/month free)
- **Setup**: 
  1. Get API key from [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
  2. Enable "Places API" and "Geocoding API"
  3. Add to `config/database.php`: `define('GOOGLE_PLACES_API_KEY', 'your-key-here');`
- **Pros**: Fastest, most accurate, excellent US coverage
- **Cons**: Requires API key, has costs after free tier

### 2. Mapbox Geocoding API ⚡ (Very Fast)
- **Speed**: ~100-200ms response time
- **Cost**: Free tier: 100,000 requests/month, then $0.50 per 1000
- **Setup**: Similar to Google, requires API key
- **Pros**: Fast, good accuracy
- **Cons**: Requires API key

### 3. Algolia Places (Fast)
- **Speed**: ~150-300ms response time
- **Cost**: Free tier: 1,000 requests/day
- **Setup**: Requires API key from Algolia
- **Pros**: Good free tier, fast
- **Cons**: Limited free tier

### 4. Nominatim (OpenStreetMap) - Current Fallback
- **Speed**: ~500-2000ms response time (slowest)
- **Cost**: Free (no API key needed)
- **Setup**: Already configured, no setup needed
- **Pros**: Completely free, no API key required
- **Cons**: Slowest option, rate limits (1 request/second)

## How It Works

1. **If Google Places API key is configured**: Uses Google Places (fastest)
2. **If no Google key**: Falls back to Nominatim (free but slower)

## Performance Comparison

- **Google Places**: ~50-100ms ⚡⚡⚡
- **Mapbox**: ~100-200ms ⚡⚡
- **Algolia**: ~150-300ms ⚡⚡
- **Nominatim**: ~500-2000ms ⚡

## Recommendation

For production, use **Google Places API**:
- Fastest response times
- Best user experience
- $200/month free credit covers ~11.7 million requests
- Automatic fallback to Nominatim if API key not configured

## Setup Instructions

### Google Places API Setup:

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a project or select existing one
3. Enable "Places API" and "Geocoding API"
4. Create credentials (API Key)
5. (Optional) Restrict API key to your domain for security
6. Add to `config/database.php`:
   ```php
   define('GOOGLE_PLACES_API_KEY', 'your-api-key-here');
   ```

The app will automatically use Google Places if the key is configured, otherwise it falls back to Nominatim.

