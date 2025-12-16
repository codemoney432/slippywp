// Map and application state
let map;
let userMarker;
let reportMarker;
let reports = [];
let reportMarkers = {}; // Store markers by report ID for easy access
let selectedCondition = null;
let selectedLocation = 'road'; // Default to road
let userLocation = null;
let turnstileWidgetId = null;
let commentTurnstileWidgetId = null;

// Initialize map
function initMap() {
    // Default to a central location (can be updated with user's location)
    const defaultCenter = [39.8283, -98.5795]; // Center of USA
    
    map = L.map('map').setView(defaultCenter, 10);
    
    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
    
    // Enable click to place pin
    map.on('click', onMapClick);
    
    // Try to get user's location
    getUserLocation();
}

// Get user's current location
function getUserLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                
                map.setView([userLocation.lat, userLocation.lng], 13);
                
                // Add user location marker (blue)
                if (userMarker) {
                    map.removeLayer(userMarker);
                }
                userMarker = L.marker([userLocation.lat, userLocation.lng], {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34]
                    })
                }).addTo(map).bindPopup('Your Location');
                
                // Automatically place report pin at user's location
                placeReportMarker(userLocation.lat, userLocation.lng);
                
                // Load reports for this area
                loadReports(userLocation.lat, userLocation.lng);
            },
            (error) => {
                console.error('Geolocation error:', error);
                // Load reports anyway
                loadReports();
            }
        );
    } else {
        console.log('Geolocation not supported');
        loadReports();
    }
}

// Helper function to place a report marker at given coordinates
function placeReportMarker(lat, lng) {
    // Remove previous report marker if exists
    if (reportMarker) {
        map.removeLayer(reportMarker);
    }
    
    // Add new marker
    reportMarker = L.marker([lat, lng], {
        icon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34]
        }),
        draggable: true
    }).addTo(map).bindPopup('Report Location').openPopup();
    
    // Update marker position on drag
    reportMarker.on('dragend', function() {
        const position = reportMarker.getLatLng();
        reportMarker.setLatLng(position).update();
    });
    
    // Enable submit button if condition is selected
    updateSubmitButton();
}

// Handle map click to place report pin
function onMapClick(e) {
    placeReportMarker(e.latlng.lat, e.latlng.lng);
}

// Location button handlers
document.querySelectorAll('.location-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Remove selected class from all location buttons
        document.querySelectorAll('.location-btn').forEach(b => b.classList.remove('selected'));
        
        // Add selected class to clicked button
        this.classList.add('selected');
        
        // Store selected location
        selectedLocation = this.dataset.location;
        document.getElementById('selected-location').value = selectedLocation;
    });
});

// Set default location button as selected (will be called after DOM is ready)
function setDefaultLocation() {
    const defaultLocationBtn = document.querySelector('.location-btn[data-location="road"]');
    if (defaultLocationBtn) {
        defaultLocationBtn.classList.add('selected');
    }
}

// Condition button handlers
document.querySelectorAll('.condition-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Remove selected class from all buttons
        document.querySelectorAll('.condition-btn').forEach(b => b.classList.remove('selected'));
        
        // Add selected class to clicked button
        this.classList.add('selected');
        
        // Store selected condition
        selectedCondition = this.dataset.condition;
        document.getElementById('selected-condition').value = selectedCondition;
        
        // Enable submit button if pin is placed
        updateSubmitButton();
    });
});

// Update submit button state
function updateSubmitButton() {
    const submitBtn = document.getElementById('submit-btn');
    const hasCondition = selectedCondition !== null;
    const hasPin = reportMarker !== null;
    // Turnstile is optional for development - removed from requirement
    // const hasTurnstile = turnstileWidgetId !== null;
    
    submitBtn.disabled = !(hasCondition && hasPin);
}

// Initialize Turnstile when DOM is ready (optional for development)
function initTurnstile() {
    // Skip Turnstile initialization for development
    // Uncomment below and set your site key to enable CAPTCHA
    /*
    if (typeof turnstile !== 'undefined') {
        turnstile.render('#turnstile-widget', {
            sitekey: 'YOUR_SITE_KEY_HERE', // Replace with your Turnstile site key
            callback: function(token) {
                turnstileWidgetId = token;
                updateSubmitButton();
            },
            'error-callback': function() {
                turnstileWidgetId = null;
                updateSubmitButton();
            }
        });
    } else {
        // Retry if Turnstile script hasn't loaded yet
        setTimeout(initTurnstile, 100);
    }
    */
}

// Search functionality
document.getElementById('search-btn').addEventListener('click', function() {
    hideAutocomplete();
    searchLocation();
});
document.getElementById('use-location-btn').addEventListener('click', getUserLocation);

document.getElementById('location-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        // Hide autocomplete immediately to prevent any pending requests
        hideAutocomplete();
        // Clear any pending autocomplete timeout
        if (autocompleteTimeout) {
            clearTimeout(autocompleteTimeout);
            autocompleteTimeout = null;
        }
        // If autocomplete is open and an item is selected, it will be handled by keydown handler
        // Otherwise, perform search
        const autocomplete = document.getElementById('location-autocomplete');
        if (autocomplete.style.display === 'none' || selectedAutocompleteIndex < 0) {
            searchLocation();
        }
    }
});

// Autocomplete functionality
let autocompleteTimeout = null;
let autocompleteResults = [];
let selectedAutocompleteIndex = -1;

// Normalize address query - expand common abbreviations
function normalizeAddressQuery(query) {
    const abbreviations = {
        'st': 'street',
        'st.': 'street',
        'ave': 'avenue',
        'ave.': 'avenue',
        'av': 'avenue',
        'av.': 'avenue',
        'rd': 'road',
        'rd.': 'road',
        'dr': 'drive',
        'dr.': 'drive',
        'ln': 'lane',
        'ln.': 'lane',
        'blvd': 'boulevard',
        'blvd.': 'boulevard',
        'ct': 'court',
        'ct.': 'court',
        'pl': 'place',
        'pl.': 'place',
        'pkwy': 'parkway',
        'pkwy.': 'parkway',
        'pa': 'pennsylvania',
        'ny': 'new york',
        'ca': 'california',
        'tx': 'texas',
        'fl': 'florida',
        'il': 'illinois',
        'oh': 'ohio',
        'mi': 'michigan',
        'nc': 'north carolina',
        'ga': 'georgia'
    };
    
    let normalized = query.toLowerCase();
    
    // Replace abbreviations (word boundaries to avoid partial matches)
    Object.keys(abbreviations).forEach(abbr => {
        const regex = new RegExp(`\\b${abbr.replace('.', '\\.')}\\b`, 'gi');
        normalized = normalized.replace(regex, abbreviations[abbr]);
    });
    
    return normalized;
}

// Clear location errors when user starts typing
document.getElementById('location-input').addEventListener('input', function(e) {
    const errorElement = document.getElementById('location-input-error');
    const inputElement = document.getElementById('location-input');
    
    if (errorElement) {
        errorElement.style.display = 'none';
        errorElement.textContent = '';
    }
    if (inputElement) {
        inputElement.classList.remove('error');
    }
    
    // Autocomplete disabled for now - logic kept intact
    // Trigger autocomplete
    const query = e.target.value.trim();
    // Skip autocomplete for zip codes (5 digits) - they should go straight to search
    const isZipCode = /^\d{5}(-\d{4})?$/.test(query);
    
    // Autocomplete temporarily disabled - uncomment below to re-enable
    /*
    if (query.length >= 3 && !isZipCode) {
        // Debounce autocomplete requests
        clearTimeout(autocompleteTimeout);
        autocompleteTimeout = setTimeout(() => {
            fetchAutocompleteSuggestions(query);
        }, 300);
    } else {
        hideAutocomplete();
    }
    */
    
    // Hide autocomplete when typing (since it's disabled)
    hideAutocomplete();
});

// Handle keyboard navigation in autocomplete
document.getElementById('location-input').addEventListener('keydown', function(e) {
    const autocomplete = document.getElementById('location-autocomplete');
    if (autocomplete.style.display === 'none' || autocompleteResults.length === 0) {
        return;
    }
    
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedAutocompleteIndex = Math.min(selectedAutocompleteIndex + 1, autocompleteResults.length - 1);
        updateAutocompleteSelection();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedAutocompleteIndex = Math.max(selectedAutocompleteIndex - 1, -1);
        updateAutocompleteSelection();
    } else if (e.key === 'Enter' && selectedAutocompleteIndex >= 0) {
        e.preventDefault();
        selectAutocompleteResult(autocompleteResults[selectedAutocompleteIndex]);
    } else if (e.key === 'Escape') {
        hideAutocomplete();
    }
});

// Hide autocomplete when clicking outside
document.addEventListener('click', function(e) {
    const input = document.getElementById('location-input');
    const autocomplete = document.getElementById('location-autocomplete');
    if (!input.contains(e.target) && !autocomplete.contains(e.target)) {
        hideAutocomplete();
    }
});

function fetchAutocompleteSuggestions(query) {
    // Try Google Places API first (much faster) if available
    if (typeof slippyConfig !== 'undefined' && slippyConfig.googlePlacesApiKey && typeof google !== 'undefined' && google.maps && google.maps.places) {
        useGooglePlacesAutocomplete(query);
    } else {
        // Fallback to Nominatim (slower but free)
        useNominatimAutocomplete(query);
    }
}

function useGooglePlacesAutocomplete(query) {
    if (!google.maps.places.AutocompleteService) {
        useNominatimAutocomplete(query);
        return;
    }
    
    const service = new google.maps.places.AutocompleteService();
    const request = {
        input: query,
        componentRestrictions: { country: 'us' },
        types: ['address', 'geocode'] // Focus on addresses
    };
    
    service.getPlacePredictions(request, (predictions, status) => {
        if (status === google.maps.places.PlacesServiceStatus.OK && predictions) {
            // Convert Google Places format to our format
            autocompleteResults = predictions.map(prediction => ({
                display_name: prediction.description,
                lat: null, // Will be fetched when selected
                lon: null,
                place_id: prediction.place_id,
                google_place: true
            }));
            displayAutocompleteResults(autocompleteResults);
        } else {
            // Fallback to Nominatim if Google fails
            useNominatimAutocomplete(query);
        }
    });
}

function useNominatimAutocomplete(query) {
    // Use Photon (Komoot's fast Nominatim alternative) - much faster than regular Nominatim
    // Photon is free, open source, and typically 3-5x faster than Nominatim
    
    // Normalize common address abbreviations for better matching
    const normalizedQuery = normalizeAddressQuery(query);
    
    // Try to extract location hints from the query for better results
    // If query contains city/state, use that to bias results
    let lat = 39.8283; // Center of US
    let lon = -98.5795;
    let zoom = 4;
    
    // Try to detect if query contains a city/state and geocode it for better bias
    // For now, use a broader search with more results
    const url = `https://photon.komoot.io/api/?q=${encodeURIComponent(normalizedQuery)}&limit=10&lang=en&lat=${lat}&lon=${lon}&zoom=${zoom}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data && data.features) {
                // Filter to US locations, calculate relevance, and format for display
                const queryLower = query.toLowerCase();
                // Extract key terms from query (numbers, street names, city, state)
                const queryTerms = queryLower.split(/[,\s]+/).filter(term => term.length > 1);
                
                autocompleteResults = data.features
                    .filter(feature => {
                        const props = feature.properties;
                        return props.country === 'United States' || props.countrycode === 'us';
                    })
                    .map(feature => {
                        const props = feature.properties;
                        const coords = feature.geometry.coordinates; // [lng, lat]
                        
                        // Format address in normal format (street on line 1, city/state/zip on line 2)
                        let addressLine1 = '';
                        let addressLine2 = '';
                        
                        if (props.housenumber && props.street) {
                            addressLine1 = `${props.housenumber} ${props.street}`;
                        } else if (props.street) {
                            addressLine1 = props.street;
                        } else if (props.name) {
                            addressLine1 = props.name;
                        }
                        
                        const cityParts = [];
                        if (props.city) cityParts.push(props.city);
                        else if (props.town) cityParts.push(props.town);
                        else if (props.village) cityParts.push(props.village);
                        
                        if (props.state) cityParts.push(props.state);
                        if (props.postcode) cityParts.push(props.postcode);
                        if (cityParts.length > 0) {
                            addressLine2 = cityParts.join(', ');
                        }
                        
                        // Create display name
                        let displayName = addressLine1;
                        if (addressLine2) {
                            displayName = `${addressLine1}\n${addressLine2}`;
                        } else if (props.name) {
                            displayName = props.name;
                        }
                        
                        // Calculate relevance score - how well this result matches the query
                        let relevanceScore = 0;
                        const searchableText = [
                            props.housenumber,
                            props.street,
                            props.name,
                            props.city,
                            props.state,
                            props.postcode
                        ].filter(Boolean).join(' ').toLowerCase();
                        
                        queryTerms.forEach(term => {
                            if (searchableText.includes(term)) {
                                // Higher score for house number matches
                                if (props.housenumber && term === props.housenumber) {
                                    relevanceScore += 5;
                                }
                                // High score for street name matches
                                else if (props.street && props.street.toLowerCase().includes(term)) {
                                    relevanceScore += 3;
                                }
                                // Medium score for city matches
                                else if (props.city && props.city.toLowerCase().includes(term)) {
                                    relevanceScore += 2;
                                }
                                // Lower score for other matches
                                else {
                                    relevanceScore += 1;
                                }
                            }
                        });
                        
                        return {
                            display_name: displayName,
                            address_line1: addressLine1,
                            address_line2: addressLine2,
                            lat: coords[1],
                            lon: coords[0],
                            google_place: false,
                            photon_result: true,
                            relevanceScore: relevanceScore
                        };
                    })
                    .filter(result => result.relevanceScore > 0) // Only show results that match at least one term
                    .sort((a, b) => {
                        // Sort by relevance score (highest first)
                        if (b.relevanceScore !== a.relevanceScore) {
                            return b.relevanceScore - a.relevanceScore;
                        }
                        // If same relevance, prefer results with house numbers (more specific)
                        const aHasNumber = a.address_line1.match(/^\d+/);
                        const bHasNumber = b.address_line1.match(/^\d+/);
                        if (aHasNumber && !bHasNumber) return -1;
                        if (!aHasNumber && bHasNumber) return 1;
                        return 0;
                    })
                    .slice(0, 5); // Limit to top 5 results
                
                if (autocompleteResults.length > 0) {
                    displayAutocompleteResults(autocompleteResults);
                } else {
                    // If Photon returned no relevant results, try Nominatim fallback
                    useNominatimFallback(query);
                }
            } else {
                // No results from Photon, try Nominatim
                useNominatimFallback(query);
            }
        })
        .catch(error => {
            console.error('Autocomplete error:', error);
            // Fallback to regular Nominatim if Photon fails
            useNominatimFallback(query);
        });
}

function useNominatimFallback(query) {
    // Fallback to regular Nominatim if Photon fails or returns no relevant results
    // Nominatim is slower but sometimes has addresses that Photon doesn't
    
    // Normalize query for better matching
    const normalizedQuery = normalizeAddressQuery(query);
    const originalQuery = query.toLowerCase();
    
    const viewbox = '-125.0,24.0,-66.0,49.0'; // Rough USA bounds
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(normalizedQuery)}&limit=10&viewbox=${viewbox}&bounded=0&countrycodes=us&addressdetails=1`;
    
    fetch(url, {
        headers: {
            'User-Agent': 'Slippy Road Conditions App'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (!data || data.length === 0) {
                hideAutocomplete();
                return;
            }
            
            // Calculate relevance using both original and normalized query terms
            const queryLower = originalQuery;
            const normalizedLower = normalizedQuery;
            const queryTerms = queryLower.split(/[,\s]+/).filter(term => term.length > 1);
            const normalizedTerms = normalizedLower.split(/[,\s]+/).filter(term => term.length > 1);
            
            autocompleteResults = data.map(result => {
                // Format Nominatim results similarly
                const address = result.address || {};
                let addressLine1 = '';
                let addressLine2 = '';
                
                if (address.house_number && address.road) {
                    addressLine1 = `${address.house_number} ${address.road}`;
                } else if (address.road) {
                    addressLine1 = address.road;
                } else if (result.display_name) {
                    const parts = result.display_name.split(',');
                    addressLine1 = parts[0] || result.display_name;
                }
                
                const cityParts = [];
                if (address.city) cityParts.push(address.city);
                else if (address.town) cityParts.push(address.town);
                else if (address.village) cityParts.push(address.village);
                
                if (address.state) cityParts.push(address.state);
                if (address.postcode) cityParts.push(address.postcode);
                if (cityParts.length > 0) {
                    addressLine2 = cityParts.join(', ');
                }
                
                let displayName = addressLine1;
                if (addressLine2) {
                    displayName = `${addressLine1}\n${addressLine2}`;
                } else {
                    displayName = result.display_name;
                }
                
                // Calculate relevance score
                let relevanceScore = 0;
                const searchableText = [
                    address.house_number,
                    address.road,
                    address.city,
                    address.state,
                    address.postcode,
                    result.display_name
                ].filter(Boolean).join(' ').toLowerCase();
                
                // Check both original and normalized terms
                const allTerms = [...new Set([...queryTerms, ...normalizedTerms])];
                allTerms.forEach(term => {
                    if (searchableText.includes(term)) {
                        if (address.house_number && (term === address.house_number || term === address.house_number.toString())) {
                            relevanceScore += 5;
                        } else if (address.road && address.road.toLowerCase().includes(term)) {
                            relevanceScore += 3;
                        } else if (address.city && address.city.toLowerCase().includes(term)) {
                            relevanceScore += 2;
                        } else {
                            relevanceScore += 1;
                        }
                    }
                });
                
                return {
                    ...result,
                    display_name: displayName,
                    address_line1: addressLine1,
                    address_line2: addressLine2,
                    google_place: false,
                    photon_result: false,
                    relevanceScore: relevanceScore
                };
            })
            .filter(result => result.relevanceScore > 0) // Only show results that match at least one term
            .sort((a, b) => {
                // Sort by relevance score (highest first)
                if (b.relevanceScore !== a.relevanceScore) {
                    return b.relevanceScore - a.relevanceScore;
                }
                // If same relevance, prefer results with house numbers (more specific)
                const aHasNumber = a.address_line1.match(/^\d+/);
                const bHasNumber = b.address_line1.match(/^\d+/);
                if (aHasNumber && !bHasNumber) return -1;
                if (!aHasNumber && bHasNumber) return 1;
                return 0;
            })
            .slice(0, 5);
            
            if (autocompleteResults.length > 0) {
                displayAutocompleteResults(autocompleteResults);
            } else {
                hideAutocomplete();
            }
        })
        .catch(error => {
            console.error('Autocomplete fallback error:', error);
            hideAutocomplete();
        });
}

function displayAutocompleteResults(results) {
    const autocomplete = document.getElementById('location-autocomplete');
    if (!autocomplete) return;
    
    if (results.length === 0) {
        hideAutocomplete();
        return;
    }
    
    selectedAutocompleteIndex = -1;
    
    autocomplete.innerHTML = results.map((result, index) => {
        // Format as two-line address if available
        let displayHTML = '';
        if (result.address_line1 && result.address_line2) {
            displayHTML = `
                <div class="autocomplete-address-line1">${escapeHtml(result.address_line1)}</div>
                <div class="autocomplete-address-line2">${escapeHtml(result.address_line2)}</div>
            `;
        } else if (result.display_name) {
            // Handle newline-separated format
            const lines = result.display_name.split('\n');
            if (lines.length > 1) {
                displayHTML = `
                    <div class="autocomplete-address-line1">${escapeHtml(lines[0])}</div>
                    <div class="autocomplete-address-line2">${escapeHtml(lines.slice(1).join(', '))}</div>
                `;
            } else {
                // Single line - truncate if too long
                const shortName = result.display_name.length > 60 ? result.display_name.substring(0, 60) + '...' : result.display_name;
                displayHTML = `<div class="autocomplete-address-line1">${escapeHtml(shortName)}</div>`;
            }
        } else {
            displayHTML = `<div class="autocomplete-address-line1">${result.lat}, ${result.lon}</div>`;
        }
        
        const displayName = result.display_name || `${result.lat}, ${result.lon}`;
        return `
            <div class="autocomplete-item" data-index="${index}" data-lat="${result.lat}" data-lng="${result.lon}" data-name="${escapeHtml(displayName)}">
                ${displayHTML}
            </div>
        `;
    }).join('');
    
    autocomplete.style.display = 'block';
    
    // Add click handlers
    autocomplete.querySelectorAll('.autocomplete-item').forEach((item, index) => {
        item.addEventListener('click', () => {
            selectAutocompleteResult(results[index]);
        });
        item.addEventListener('mouseenter', () => {
            selectedAutocompleteIndex = index;
            updateAutocompleteSelection();
        });
    });
}

function updateAutocompleteSelection() {
    const items = document.querySelectorAll('.autocomplete-item');
    items.forEach((item, index) => {
        if (index === selectedAutocompleteIndex) {
            item.classList.add('selected');
        } else {
            item.classList.remove('selected');
        }
    });
}

function selectAutocompleteResult(result) {
    const input = document.getElementById('location-input');
    
    // Set input value to formatted address (line 1, line 2)
    if (result.address_line1 && result.address_line2) {
        input.value = `${result.address_line1}, ${result.address_line2}`;
    } else if (result.display_name) {
        // Replace newlines with commas for input field
        input.value = result.display_name.replace(/\n/g, ', ');
    } else {
        input.value = `${result.lat}, ${result.lon}`;
    }
    
    hideAutocomplete();
    
    // If it's a Google Place, we need to get the coordinates first
    if (result.google_place && result.place_id && typeof google !== 'undefined' && google.maps && google.maps.places) {
        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({ placeId: result.place_id }, (results, status) => {
            if (status === 'OK' && results && results[0]) {
                const location = results[0].geometry.location;
                const lat = location.lat();
                const lng = location.lng();
                const displayName = result.address_line1 && result.address_line2 
                    ? `${result.address_line1}, ${result.address_line2}`
                    : result.display_name;
                centerMapOnLocation(lat, lng, displayName);
            } else {
                // Fallback to regular search
                searchLocation();
            }
        });
    } else if (result.lat && result.lon) {
        // Photon or Nominatim result - we have coordinates
        const lat = parseFloat(result.lat);
        const lng = parseFloat(result.lon);
        const displayName = result.address_line1 && result.address_line2 
            ? `${result.address_line1}, ${result.address_line2}`
            : result.display_name;
        centerMapOnLocation(lat, lng, displayName);
    } else {
        // No coordinates - perform regular search
        searchLocation();
    }
}

function centerMapOnLocation(lat, lng, displayName) {
    map.setView([lat, lng], 13);
    
    // Update user location marker
    if (userMarker) {
        map.removeLayer(userMarker);
    }
    userMarker = L.marker([lat, lng], {
        icon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34]
        })
    }).addTo(map).bindPopup(displayName || 'Searched Location');
    
    userLocation = { lat, lng };
    loadReports(lat, lng);
}

function hideAutocomplete() {
    const autocomplete = document.getElementById('location-autocomplete');
    if (autocomplete) {
        autocomplete.style.display = 'none';
        autocomplete.innerHTML = '';
    }
    autocompleteResults = [];
    selectedAutocompleteIndex = -1;
}

function searchLocation() {
    const query = document.getElementById('location-input').value.trim();
    const errorElement = document.getElementById('location-input-error');
    const inputElement = document.getElementById('location-input');
    
    // Clear previous errors
    if (errorElement) {
        errorElement.style.display = 'none';
        errorElement.textContent = '';
    }
    if (inputElement) {
        inputElement.classList.remove('error');
    }
    
    if (!query) {
        showLocationError('Please enter a zip code or address');
        return;
    }
    
    // Check if input is a US zip code (5 digits)
    const isZipCode = /^\d{5}(-\d{4})?$/.test(query);
    
    // For zip codes, use Nominatim postalcode parameter directly (most accurate)
    // For addresses, use Photon first (much faster)
    if (isZipCode) {
        useNominatimForZipCode(query);
    } else {
        // Use Photon API first for addresses (much faster than Nominatim)
        // Normalize query for better matching
        const normalizedQuery = normalizeAddressQuery(query);
        const photonUrl = `https://photon.komoot.io/api/?q=${encodeURIComponent(normalizedQuery)}&limit=10&lang=en&lat=39.8283&lon=-98.5795&zoom=4`;
        
        // Add timeout to Photon request (3 seconds - should be fast)
        const photonPromise = fetch(photonUrl);
        const timeoutPromise = new Promise((_, reject) => 
            setTimeout(() => reject(new Error('Photon request timeout')), 3000)
        );
        
        Promise.race([photonPromise, timeoutPromise])
            .then(response => {
                if (!(response instanceof Response)) {
                    throw new Error('Invalid response type');
                }
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data && data.features && data.features.length > 0) {
                    // Filter to US locations
                    const usResults = data.features.filter(feature => {
                        const props = feature.properties;
                        return props.country === 'United States' || props.countrycode === 'us';
                    });
                    
                    if (usResults.length > 0) {
                        const result = usResults[0];
                        const coords = result.geometry.coordinates; // [lng, lat]
                        const lat = coords[1];
                        const lng = coords[0];
                        
                        if (isNaN(lat) || isNaN(lng)) {
                            throw new Error('Invalid coordinates in Photon response');
                        }
                        
                        centerMapOnLocation(lat, lng, result.properties.name || query);
                        return; // Success, exit early
                    }
                }
                
                // Fallback to Nominatim if Photon fails or returns no US results
                useNominatimForSearch(query, false);
            })
            .catch(error => {
                console.error('Photon geocoding error:', error);
                // Fallback to Nominatim on error
                useNominatimForSearch(query, false);
            });
    }
}

// Optimized function for zip code lookups using local database
function useNominatimForZipCode(zipCode) {
    // Extract just the zip code (handle 5+4 format, remove any extra text)
    const zipOnly = zipCode.replace(/,?\s*USA/i, '').trim().split('-')[0]; // Get just the 5-digit part
    
    // Use local database API endpoint (much faster than Nominatim)
    const url = `api/get_zip_code.php?zip=${encodeURIComponent(zipOnly)}`;
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                if (response.status === 404) {
                    throw new Error('Zip code not found');
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success && data.latitude && data.longitude) {
                const lat = parseFloat(data.latitude);
                const lng = parseFloat(data.longitude);
                
                if (isNaN(lat) || isNaN(lng)) {
                    throw new Error('Invalid coordinates in response');
                }
                
                centerMapOnLocation(lat, lng, data.display_name || zipCode);
            } else {
                showLocationError('Zip code not found. Please verify the zip code and try again.');
            }
        })
        .catch(error => {
            console.error('Zip code lookup error:', error);
            // Fallback to Nominatim if local database doesn't have the zip code
            useNominatimForZipCodeFallback(zipCode);
        });
}

// Fallback to Nominatim if zip code not found in local database
function useNominatimForZipCodeFallback(zipCode) {
    // Extract just the zip code (handle 5+4 format, remove any extra text)
    const zipOnly = zipCode.replace(/,?\s*USA/i, '').trim().split('-')[0]; // Get just the 5-digit part
    
    // Use postalcode parameter - this is the most accurate way to search for zip codes
    // Optimized: limit=1 (we only need the first result), minimal parameters for speed
    const url = `https://nominatim.openstreetmap.org/search?format=json&postalcode=${encodeURIComponent(zipOnly)}&countrycodes=us&limit=1&addressdetails=1`;
    
    fetch(url, {
        headers: {
            'User-Agent': 'Slippy Road Conditions App'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data && Array.isArray(data) && data.length > 0) {
                const result = data[0];
                
                // Verify it's actually the correct zip code
                const zipDigits = zipOnly;
                if (result.address && result.address.postcode) {
                    const resultZip = result.address.postcode.replace('-', '').replace(/\s/g, '');
                    if (resultZip !== zipDigits) {
                        // Zip code doesn't match, show error
                        showLocationError('Zip code not found. Please verify the zip code and try again.');
                        return;
                    }
                }
                
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                
                if (isNaN(lat) || isNaN(lng)) {
                    throw new Error('Invalid coordinates in response');
                }
                
                centerMapOnLocation(lat, lng, result.display_name || zipCode);
            } else {
                showLocationError('Zip code not found. Please verify the zip code and try again.');
            }
        })
        .catch(error => {
            console.error('Zip code geocoding error:', error);
            showLocationError('Error searching for zip code. Please try again.');
        });
}

function useNominatimForSearch(searchQuery, isZipCode) {
    // For zip codes, try local database first, then fallback to Nominatim
    if (isZipCode) {
        const zipOnly = searchQuery.replace(/,?\s*USA/i, '').trim().split('-')[0];
        useNominatimForZipCode(zipOnly);
        return;
    }
    
    // Fallback to Nominatim for addresses (slower but more comprehensive)
    // Only used if Photon fails or doesn't find results
    const viewbox = '-125.0,24.0,-66.0,49.0'; // Rough USA bounds
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery)}&limit=10&viewbox=${viewbox}&bounded=0&countrycodes=us&addressdetails=1`;
    
    fetch(url, {
        headers: {
            'User-Agent': 'Slippy Road Conditions App'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data && Array.isArray(data) && data.length > 0) {
                let result = data[0];
                
                // For zip codes, prioritize exact postcode matches
                if (isZipCode) {
                    // Extract zip code digits (remove dash)
                    const zipDigits = searchQuery.replace('-', '').replace(/,?\s*USA/i, '').trim();
                    
                    // First, try to find exact postcode match in address details
                    const exactMatch = data.find(r => {
                        if (r.address && r.address.postcode) {
                            const resultZip = r.address.postcode.replace('-', '').replace(/\s/g, '');
                            return resultZip === zipDigits;
                        }
                        return false;
                    });
                    
                    if (exactMatch) {
                        result = exactMatch;
                    } else {
                        // Try to find a result with type "postcode" or class "place" with type "postcode"
                        const postcodeResult = data.find(r => 
                            r.type === 'postcode' || 
                            (r.class === 'place' && r.type === 'postcode')
                        );
                        if (postcodeResult) {
                            result = postcodeResult;
                        }
                        // If still no good match, use first result (should be most relevant when using postalcode param)
                    }
                }
                
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                
                if (isNaN(lat) || isNaN(lng)) {
                    throw new Error('Invalid coordinates in response');
                }
                
                centerMapOnLocation(lat, lng, result.display_name || searchQuery);
            } else {
                showLocationError('Location not found. Please try a different search term or include the city/state.');
            }
        })
        .catch(error => {
            console.error('Geocoding error:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                searchQuery: searchQuery,
                isZipCode: isZipCode
            });
            showLocationError('Error searching for location. Please try again.');
        });
}

// Helper function to show location input errors
function showLocationError(message) {
    const errorElement = document.getElementById('location-input-error');
    const inputElement = document.getElementById('location-input');
    
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
    
    if (inputElement) {
        inputElement.classList.add('error');
        inputElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Load reports from API
function loadReports(lat = null, lng = null) {
    // Load reports for the list (conditional: 24h if >=20, otherwise most recent 20)
    let listUrl = 'api/get_reports.php?limit=20';
    if (lat !== null && lng !== null) {
        listUrl += `&lat=${lat}&lng=${lng}&radius=96.56`; // 60 miles radius (96.56 km)
    }
    
    // Load reports for the map (strict 24-hour filter only, no limit to show all recent reports)
    let mapUrl = 'api/get_reports.php?limit=1000&strict_24h=true';
    if (lat !== null && lng !== null) {
        mapUrl += `&lat=${lat}&lng=${lng}&radius=96.56`; // 60 miles radius (96.56 km)
    }
    
    // Fetch reports for list (conditional logic)
    fetch(listUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            reports = data.reports || [];
            console.log('Loaded reports for list:', reports.length);
            displayReports();
        })
        .catch(error => {
            console.error('Error loading reports for list:', error);
            const errorMsg = error.message || 'Unknown error';
            document.getElementById('reports-container').innerHTML = 
                `<p class="loading">Error loading reports: ${errorMsg}<br>Check browser console for details.</p>`;
        });
    
    // Fetch reports for map (strict 24-hour filter)
    console.log('Fetching map reports with strict 24h filter:', mapUrl);
    fetch(mapUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            const mapReports = data.reports || [];
            console.log('Loaded reports for map (24h only):', mapReports.length);
            // Log report ages for debugging
            mapReports.forEach(report => {
                // Parse ISO 8601 UTC format
                const reportDate = new Date(report.created_at);
                const hoursAgo = (Date.now() - reportDate.getTime()) / (1000 * 60 * 60);
                console.log(`Report ${report.id}: ${hoursAgo.toFixed(1)} hours ago`);
            });
            displayReportMarkers(mapReports);
        })
        .catch(error => {
            console.error('Error loading reports for map:', error);
            // Don't show error to user for map, just log it
        });
}

// Display reports in the list
function displayReports() {
    const container = document.getElementById('reports-container');
    
    if (reports.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <p>No reports yet in this area.</p>
                <p>Be the first to report a condition!</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = reports.map(report => {
        const timeAgo = getTimeAgo(report.created_at);
        const conditionLabel = getConditionLabel(report.condition_type);
        const conditionTypeName = report.condition_type.charAt(0).toUpperCase() + report.condition_type.slice(1);
        const locationType = report.location_type || 'road';
        const locationLabel = getLocationLabel(locationType);
        const submitterInfo = report.submitter_name 
            ? `<div class="report-submitter">Reported by: ${escapeHtml(report.submitter_name)}</div>` 
            : '';
        const upvotes = report.upvotes || 0;
        const downvotes = report.downvotes || 0;
        const commentCount = report.comment_count || 0;
        
        return `
            <div class="report-item" data-report-id="${report.id}" data-lat="${report.latitude}" data-lng="${report.longitude}">
                <div class="report-item-header">
                    <span class="condition-badge ${report.condition_type}">${conditionLabel}</span>
                    <span class="report-time">${timeAgo}</span>
                </div>
                <div class="report-condition-type">Condition: ${conditionTypeName}</div>
                <div class="report-location-type">📍 ${locationLabel}</div>
                <div class="report-intersection">
                    ${report.intersection 
                        ? `<span class="clickable-location" data-report-id="${report.id}" data-lat="${report.latitude}" data-lng="${report.longitude}" style="cursor: pointer; text-decoration: underline;">📍near ${escapeHtml(report.intersection)}</span>`
                        : '📍 Location unavailable'}
                </div>
                ${submitterInfo}
                <div class="vote-section">
                    <button class="vote-btn upvote" data-report-id="${report.id}" data-vote-type="up">
                        👍 <span class="vote-count" id="upvote-${report.id}">${upvotes}</span>
                    </button>
                    <button class="vote-btn downvote" data-report-id="${report.id}" data-vote-type="down">
                        👎 <span class="vote-count" id="downvote-${report.id}">${downvotes}</span>
                    </button>
                    <button class="comment-btn" data-report-id="${report.id}">
                        💬 Comments
                        ${commentCount > 0 ? `<span class="comment-count">${commentCount}</span>` : ''}
                    </button>
                    <button class="delete-btn admin-only" data-report-id="${report.id}" style="display: ${isAdmin ? 'inline-block' : 'none'};">
                        🗑️ Delete
                    </button>
                </div>
            </div>
        `;
    }).join('');
    
    // Add event listeners for voting
    document.querySelectorAll('.vote-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const reportId = parseInt(this.dataset.reportId);
            const voteType = this.dataset.voteType;
            submitVote(reportId, voteType);
        });
    });
    
    // Add event listeners for comments
    document.querySelectorAll('.comment-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const reportId = parseInt(this.dataset.reportId);
            openCommentModal(reportId);
        });
    });
    
    // Add event listeners for delete buttons
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const reportId = parseInt(this.dataset.reportId);
            deleteReport(reportId);
        });
    });
    
    // Add event listeners for clickable locations
    document.querySelectorAll('.clickable-location').forEach(location => {
        location.addEventListener('click', function() {
            const reportId = parseInt(this.dataset.reportId);
            const lat = parseFloat(this.dataset.lat);
            const lng = parseFloat(this.dataset.lng);
            centerMapOnReport(reportId, lat, lng);
        });
    });
    
    // Update admin UI visibility
    updateAdminUI();
}

// Display report markers on map
function displayReportMarkers(mapReports = null) {
    // Use provided mapReports or fall back to global reports array
    const reportsToDisplay = mapReports !== null ? mapReports : reports;
    
    // Remove existing report markers (except user's report marker and report placement marker)
    map.eachLayer(layer => {
        if ((layer instanceof L.Marker || layer instanceof L.CircleMarker) && layer !== userMarker && layer !== reportMarker) {
            map.removeLayer(layer);
        }
    });
    
    // Clear the reportMarkers cache
    reportMarkers = {};
    
    // Add markers for each report
    reportsToDisplay.forEach(report => {
        // Color-coded by condition type: ice=red, slush=blue, snow=purple, water=orange
        const conditionColors = {
            'ice': '#ef4444',      // Red
            'slush': '#3b82f6',   // Blue
            'snow': '#8b5cf6',    // Purple
            'water': '#f97316'    // Orange
        };
        
        const locationType = report.location_type || 'road';
        const color = conditionColors[report.condition_type] || '#667eea';
        const isSidewalk = locationType === 'sidewalk';
        
        let marker;
        
        if (isSidewalk) {
            // Square marker for sidewalk reports
            const size = 16; // Size in pixels
            const squareIcon = L.divIcon({
                className: 'custom-square-marker',
                html: `<div style="width: ${size}px; height: ${size}px; background-color: ${color}; border: 2px solid #1f2937; border-radius: 2px;"></div>`,
                iconSize: [size, size],
                iconAnchor: [size/2, size/2]
            });
            marker = L.marker([report.latitude, report.longitude], { icon: squareIcon }).addTo(map);
        } else {
            // Circle marker for road reports
            const markerStyle = {
                radius: 8,
                fillColor: color,
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.9
            };
            marker = L.circleMarker([report.latitude, report.longitude], markerStyle).addTo(map);
        }
        
        const conditionLabel = getConditionLabel(report.condition_type);
        const locationLabel = getLocationLabel(locationType);
        const submitterInfo = report.submitter_name ? ` by ${report.submitter_name}` : '';
        const timeAgo = getTimeAgo(report.created_at);
        
        marker.bindPopup(`
            <strong>${conditionLabel}</strong><br>
            <small>📍 ${locationLabel}</small><br>
            ${submitterInfo}<br>
            <small>${timeAgo}</small>
        `);
        
        // Store marker by report ID for easy access
        reportMarkers[report.id] = marker;
    });
}

// Center map on a specific report and highlight it
function centerMapOnReport(reportId, lat, lng) {
    // Scroll the map container into view first
    const mapContainer = document.querySelector('.map-container');
    if (mapContainer) {
        mapContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    // Small delay to ensure scroll has started before centering map
    setTimeout(() => {
        // Center and zoom the map
        map.setView([lat, lng], 15);
        
        // Check if marker exists in reportMarkers
        if (reportMarkers[reportId]) {
            // Marker is already on the map - open its popup and bring to front
            reportMarkers[reportId].openPopup();
            reportMarkers[reportId].bringToFront();
        } else {
            // Marker not currently displayed - create a temporary highlighted marker
            const highlightMarker = L.marker([lat, lng], {
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                    iconSize: [35, 51], // Larger than normal
                    iconAnchor: [17, 51],
                    popupAnchor: [1, -34]
                })
            }).addTo(map);
            
            // Find the report data
            const report = reports.find(r => r.id === reportId);
            if (report) {
                const conditionLabel = getConditionLabel(report.condition_type);
                const locationLabel = getLocationLabel(report.location_type || 'road');
                const submitterInfo = report.submitter_name ? ` by ${report.submitter_name}` : '';
                const timeAgo = getTimeAgo(report.created_at);
                
                highlightMarker.bindPopup(`
                    <strong>${conditionLabel}</strong><br>
                    <small>📍 ${locationLabel}</small><br>
                    ${submitterInfo}<br>
                    <small>${timeAgo}</small>
                `).openPopup();
            } else {
                highlightMarker.bindPopup('Report Location').openPopup();
            }
            
            // Remove highlight marker after 5 seconds
            setTimeout(() => {
                if (map.hasLayer(highlightMarker)) {
                    map.removeLayer(highlightMarker);
                }
            }, 5000);
        }
    }, 100);
    
    // Scroll the report item into view in the list
    const reportElement = document.querySelector(`.report-item[data-report-id="${reportId}"]`);
    if (reportElement) {
        // Add a temporary highlight class
        reportElement.classList.add('highlighted-report');
        setTimeout(() => {
            reportElement.classList.remove('highlighted-report');
        }, 2000);
    }
}

// Get condition label
function getConditionLabel(condition) {
    const labels = {
        'ice': '🧊 Ice',
        'slush': '🌨️ Slush',
        'snow': '❄️ Snow',
        'water': '💧 Water'
    };
    return labels[condition] || condition;
}

// Get location type label
function getLocationLabel(locationType) {
    const labels = {
        'road': 'Road',
        'sidewalk': 'Sidewalk'
    };
    return labels[locationType] || locationType;
}

// Intersection is now stored in the database and included in the report data
// No need to fetch it separately anymore

// Get time ago string
function getTimeAgo(dateString) {
    // Parse ISO 8601 UTC format (e.g., '2025-12-15T14:30:00Z')
    // JavaScript's Date constructor handles ISO 8601 correctly
    const date = new Date(dateString);
    
    // Check if date is valid
    if (isNaN(date.getTime())) {
        // Fallback: try parsing as MySQL timestamp format
        const dateParts = dateString.split(/[- :]/);
        if (dateParts.length >= 3) {
            return getTimeAgoFromParts(dateParts);
        }
        return 'Unknown time';
    }
    
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
    if (hours < 24) return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
    return `${days} day${days !== 1 ? 's' : ''} ago`;
}

// Fallback function for MySQL timestamp format
function getTimeAgoFromParts(dateParts) {
    const date = new Date(Date.UTC(
        parseInt(dateParts[0]),      // year
        parseInt(dateParts[1]) - 1,   // month (0-indexed)
        parseInt(dateParts[2]),       // day
        parseInt(dateParts[3] || 0),  // hour
        parseInt(dateParts[4] || 0),  // minute
        parseInt(dateParts[5] || 0)   // second
    ));
    
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
    if (hours < 24) return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
    return `${days} day${days !== 1 ? 's' : ''} ago`;
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Show success modal and auto-dismiss after 10 seconds
function showSuccessModal() {
    const modal = document.getElementById('success-modal');
    if (modal) {
        modal.style.display = 'block';
        
        // Auto-dismiss after 10 seconds
        setTimeout(() => {
            modal.style.display = 'none';
        }, 10000);
    }
}

// Close success modal (if user clicks outside or wants to close manually)
window.addEventListener('click', function(event) {
    const successModal = document.getElementById('success-modal');
    if (event.target === successModal) {
        successModal.style.display = 'none';
    }
});

// Helper functions for validation messages
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorElement = document.getElementById(fieldId + '-error');
    
    if (field && errorElement) {
        field.classList.add('error');
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        
        // Scroll to field if needed
        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    const errorElement = document.getElementById(fieldId + '-error');
    
    if (field) {
        field.classList.remove('error');
    }
    if (errorElement) {
        errorElement.style.display = 'none';
        errorElement.textContent = '';
    }
}

function showFormError(message) {
    const formError = document.getElementById('form-error');
    if (formError) {
        formError.textContent = message;
        formError.style.display = 'block';
        
        // Scroll to error
        formError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function clearFormError() {
    const formError = document.getElementById('form-error');
    if (formError) {
        formError.style.display = 'none';
        formError.textContent = '';
    }
}

// Clear field errors when user starts typing
document.getElementById('submitter-name').addEventListener('input', function() {
    clearFieldError('submitter-name');
    clearFormError();
});

// Handle form submission
document.getElementById('report-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Clear previous errors
    clearFieldError('submitter-name');
    clearFormError();
    
    if (!reportMarker || !selectedCondition) {
        showFormError('Please select a condition type and place a pin on the map.');
        return;
    }
    
    const position = reportMarker.getLatLng();
    const submitterName = document.getElementById('submitter-name').value.trim() || null;
    
    const reportData = {
        latitude: position.lat,
        longitude: position.lng,
        condition_type: selectedCondition,
        location_type: selectedLocation,
        submitter_name: submitterName,
        turnstile_token: turnstileWidgetId || '' // Optional for development
    };
    
    // Disable submit button during submission
    const submitBtn = document.getElementById('submit-btn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    fetch('api/create_report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(reportData)
    })
    .then(response => {
        // Check if response is ok
        if (!response.ok) {
            return response.json().then(data => {
                // Check if it's a 400 error (likely banned word or validation error)
                if (response.status === 400 && data.error) {
                    // Check if error message suggests it's a name validation issue
                    if (data.error.includes('inappropriate') || data.error.includes('Name')) {
                        throw { type: 'field', field: 'submitter-name', message: data.error };
                    }
                    throw { type: 'form', message: data.error };
                }
                throw { type: 'form', message: data.error || `HTTP error! status: ${response.status}` };
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success modal
            showSuccessModal();
            
            // Clear all errors
            clearFieldError('submitter-name');
            clearFormError();
            
            // Reset form
            document.getElementById('report-form').reset();
            document.querySelectorAll('.condition-btn').forEach(b => b.classList.remove('selected'));
            selectedCondition = null;
            
            // Reset location type to default (road)
            document.querySelectorAll('.location-btn').forEach(b => b.classList.remove('selected'));
            const defaultLocationBtn = document.querySelector('.location-btn[data-location="road"]');
            if (defaultLocationBtn) {
                defaultLocationBtn.classList.add('selected');
            }
            selectedLocation = 'road';
            document.getElementById('selected-location').value = 'road';
            
            // Remove report marker
            if (reportMarker) {
                map.removeLayer(reportMarker);
                reportMarker = null;
            }
            
            // Reset Turnstile
            if (turnstileWidgetId && typeof turnstile !== 'undefined') {
                turnstile.reset(turnstileWidgetId);
                turnstileWidgetId = null;
            }
            
            // Reload reports
            if (userLocation) {
                loadReports(userLocation.lat, userLocation.lng);
            } else {
                loadReports();
            }
        } else {
            showFormError('Error: ' + (data.error || 'Failed to submit report'));
        }
    })
    .catch(error => {
        console.error('Submission error:', error);
        
        // Handle different error types
        if (error.type === 'field') {
            showFieldError(error.field, error.message);
        } else if (error.type === 'form') {
            showFormError(error.message);
        } else {
            // Generic error handling
            const errorMessage = error.message || 'An unexpected error occurred. Please try again.';
            showFormError('Error submitting report: ' + errorMessage);
        }
    })
    .finally(() => {
        submitBtn.textContent = 'Submit Report';
        updateSubmitButton();
    });
});

// Voting functionality
function submitVote(reportId, voteType) {
    fetch('api/vote.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            report_id: reportId,
            vote_type: voteType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update vote counts
            const upvoteEl = document.getElementById(`upvote-${reportId}`);
            const downvoteEl = document.getElementById(`downvote-${reportId}`);
            if (upvoteEl) upvoteEl.textContent = data.upvotes;
            if (downvoteEl) downvoteEl.textContent = data.downvotes;
            
            // Update button states
            const upvoteBtn = document.querySelector(`.upvote[data-report-id="${reportId}"]`);
            const downvoteBtn = document.querySelector(`.downvote[data-report-id="${reportId}"]`);
            
            if (voteType === 'up') {
                upvoteBtn.classList.add('active');
                downvoteBtn.classList.remove('active');
            } else {
                downvoteBtn.classList.add('active');
                upvoteBtn.classList.remove('active');
            }
        } else {
            alert('Error: ' + (data.error || 'Failed to submit vote'));
        }
    })
    .catch(error => {
        console.error('Vote error:', error);
        alert('Error submitting vote. Please try again.');
    });
}

// Comment modal functionality
let currentReportId = null;

function openCommentModal(reportId) {
    currentReportId = reportId;
    const modal = document.getElementById('comment-modal');
    modal.style.display = 'block';
    loadComments(reportId);
    initCommentTurnstile();
}

function closeCommentModal() {
    const modal = document.getElementById('comment-modal');
    modal.style.display = 'none';
    currentReportId = null;
    document.getElementById('comment-input').value = '';
    document.getElementById('char-count').textContent = '0';
    // Reset Turnstile
    if (commentTurnstileWidgetId && typeof turnstile !== 'undefined') {
        turnstile.reset(commentTurnstileWidgetId);
        commentTurnstileWidgetId = null;
    }
    updateCommentSubmitButton();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('comment-modal');
    if (event.target === modal) {
        closeCommentModal();
    }
}

// Close modal button
document.addEventListener('DOMContentLoaded', function() {
    const closeBtn = document.querySelector('.close-modal');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeCommentModal);
    }
    
    // Comment character counter
    const commentInput = document.getElementById('comment-input');
    if (commentInput) {
        commentInput.addEventListener('input', function() {
            document.getElementById('char-count').textContent = this.value.length;
            updateCommentSubmitButton();
        });
    }
    
    // Submit comment
    const submitCommentBtn = document.getElementById('submit-comment-btn');
    if (submitCommentBtn) {
        submitCommentBtn.addEventListener('click', function() {
            if (currentReportId) {
                submitComment(currentReportId);
            }
        });
    }
    
    // Submit on Enter (Ctrl+Enter)
    if (commentInput) {
        commentInput.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                if (currentReportId) {
                    submitComment(currentReportId);
                }
            }
        });
    }
});

function loadComments(reportId) {
    fetch(`api/get_comments.php?report_id=${reportId}`)
        .then(response => response.json())
        .then(data => {
            const commentsList = document.getElementById('comments-list');
            // Remove any existing pending messages before reloading (but preserve them if they exist)
            const pendingMessages = commentsList.querySelectorAll('.pending-comment');
            const pendingHTML = Array.from(pendingMessages).map(msg => msg.outerHTML).join('');
            
            if (data.comments && data.comments.length > 0) {
                const newCommentsHTML = data.comments.map(comment => {
                    const timeAgo = getTimeAgo(comment.created_at);
                    return `
                        <div class="comment-item">
                            <div class="comment-text">${escapeHtml(comment.comment_text)}</div>
                            <div class="comment-time">${timeAgo}</div>
                        </div>
                    `;
                }).join('');
                commentsList.innerHTML = newCommentsHTML + pendingHTML;
            } else {
                // Only show "no comments" if there are no pending messages
                if (pendingMessages.length === 0) {
                    commentsList.innerHTML = '<p class="loading">No comments yet. Be the first to comment!</p>';
                } else {
                    commentsList.innerHTML = pendingHTML;
                }
            }
        })
        .catch(error => {
            console.error('Error loading comments:', error);
            const commentsList = document.getElementById('comments-list');
            const pendingMessages = commentsList.querySelectorAll('.pending-comment');
            // Only show error if there are no pending messages
            if (pendingMessages.length === 0) {
                commentsList.innerHTML = '<p class="loading">Error loading comments.</p>';
            }
        });
}

// Initialize Turnstile for comments (optional for development)
function initCommentTurnstile() {
    // Skip Turnstile initialization for development
    // Uncomment below and set your site key to enable CAPTCHA
    /*
    const widgetContainer = document.getElementById('comment-turnstile-widget');
    if (!widgetContainer) return;
    
    widgetContainer.innerHTML = ''; // Clear previous widget
    
    if (typeof turnstile !== 'undefined') {
        commentTurnstileWidgetId = turnstile.render('#comment-turnstile-widget', {
            sitekey: 'YOUR_SITE_KEY_HERE', // Replace with your Turnstile site key
            callback: function(token) {
                commentTurnstileWidgetId = token;
                updateCommentSubmitButton();
            },
            'error-callback': function() {
                commentTurnstileWidgetId = null;
                updateCommentSubmitButton();
            }
        });
    } else {
        // Retry if Turnstile script hasn't loaded yet
        setTimeout(initCommentTurnstile, 100);
    }
    */
}

// Update comment submit button state
function updateCommentSubmitButton() {
    const submitBtn = document.getElementById('submit-comment-btn');
    if (!submitBtn) return;
    
    const hasComment = document.getElementById('comment-input').value.trim().length > 0;
    // Turnstile is optional for development - removed from requirement
    // const hasTurnstile = commentTurnstileWidgetId !== null;
    
    submitBtn.disabled = !hasComment;
}

function submitComment(reportId) {
    const commentText = document.getElementById('comment-input').value.trim();
    
    if (!commentText) {
        alert('Please enter a comment');
        return;
    }
    
    if (commentText.length > 500) {
        alert('Comment is too long (max 500 characters)');
        return;
    }
    
    // CAPTCHA check (optional for development)
    // if (!commentTurnstileWidgetId) {
    //     alert('Please complete the CAPTCHA');
    //     return;
    // }
    
    const submitBtn = document.getElementById('submit-comment-btn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Posting...';
    
    fetch('api/comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            report_id: reportId,
            comment_text: commentText,
            turnstile_token: commentTurnstileWidgetId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear input
            document.getElementById('comment-input').value = '';
            document.getElementById('char-count').textContent = '0';
            
            // Reset Turnstile
            if (commentTurnstileWidgetId && typeof turnstile !== 'undefined') {
                turnstile.reset(commentTurnstileWidgetId);
                commentTurnstileWidgetId = null;
            }
            updateCommentSubmitButton();
            
            // Show submission message (comment is pending moderation)
            const commentsList = document.getElementById('comments-list');
            const pendingMessage = document.createElement('div');
            pendingMessage.className = 'comment-item pending-comment';
            pendingMessage.style.cssText = 'background: #f0f9ff; border-left: 3px solid #3b82f6; padding: 16px; margin-bottom: 12px; border-radius: 4px;';
            pendingMessage.innerHTML = `
                <div class="comment-text" style="color: #1e40af; font-style: italic;">⏳ Comment has been submitted and is pending moderation.</div>
            `;
            commentsList.appendChild(pendingMessage);
            
            // Remove the pending message after 5 seconds (comment won't show until approved)
            setTimeout(() => {
                if (pendingMessage.parentNode) {
                    pendingMessage.remove();
                }
            }, 5000);
            
            // Don't reload comments immediately - they're pending moderation
            // The comment will appear automatically once approved by the cron job
            // User can refresh manually if they want to check
            
            // Update comment count in report list (won't change until approved)
            // We'll let the user refresh to see updated counts
        } else {
            alert('Error: ' + (data.error || 'Failed to submit comment'));
        }
    })
    .catch(error => {
        console.error('Comment error:', error);
        alert('Error submitting comment. Please try again.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Post Comment';
    });
}

// Admin functionality
let isAdmin = false;

// Check admin status
function checkAdminStatus() {
    fetch('api/check_admin.php')
        .then(response => response.json())
        .then(data => {
            isAdmin = data.is_admin || false;
            updateAdminUI();
        })
        .catch(error => {
            console.error('Error checking admin status:', error);
            isAdmin = false;
            updateAdminUI();
        });
}

// Update admin UI visibility (only show/hide delete buttons based on backend authentication)
function updateAdminUI() {
    const deleteButtons = document.querySelectorAll('.admin-only');
    
    if (isAdmin) {
        deleteButtons.forEach(btn => {
            btn.style.display = 'inline-block';
        });
    } else {
        deleteButtons.forEach(btn => {
            btn.style.display = 'none';
        });
    }
}

// Delete report
function deleteReport(reportId) {
    if (!confirm('Are you sure you want to delete this report? This action cannot be undone.')) {
        return;
    }
    
    fetch('api/delete_report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ report_id: reportId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove report from display
            const reportElement = document.querySelector(`.report-item[data-report-id="${reportId}"]`);
            if (reportElement) {
                reportElement.remove();
            }
            
            // Reload reports to update map
            if (userLocation) {
                loadReports(userLocation.lat, userLocation.lng);
            } else {
                loadReports();
            }
        } else {
            alert('Error: ' + (data.error || 'Failed to delete report'));
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        alert('Error deleting report. Please try again.');
    });
}

// Initialize app when page loads
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    initTurnstile();
    setDefaultLocation();
    
    // Check admin status (authentication handled by backend)
    checkAdminStatus();
    
    // Load initial reports
    setTimeout(() => {
        if (userLocation) {
            loadReports(userLocation.lat, userLocation.lng);
        } else {
            loadReports();
        }
    }, 1000);
});

