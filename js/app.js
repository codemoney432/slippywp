// Map and application state
let map;
let userMarker;
let reportMarker;
let reports = [];
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
                
                // Add user location marker
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

// Handle map click to place report pin
function onMapClick(e) {
    const lat = e.latlng.lat;
    const lng = e.latlng.lng;
    
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
document.getElementById('search-btn').addEventListener('click', searchLocation);
document.getElementById('use-location-btn').addEventListener('click', getUserLocation);

document.getElementById('location-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchLocation();
    }
});

function searchLocation() {
    const query = document.getElementById('location-input').value.trim();
    
    if (!query) {
        alert('Please enter a zip code or address');
        return;
    }
    
    // Check if input is a US zip code (5 digits)
    const isZipCode = /^\d{5}(-\d{4})?$/.test(query);
    let searchQuery = query;
    
    // If it's a zip code, add "USA" to make it more specific
    if (isZipCode) {
        searchQuery = `${query}, USA`;
    }
    
    // Use Nominatim (OpenStreetMap geocoding API)
    // Add viewbox to bias results to North America (roughly USA bounds)
    // viewbox format: min_lon,min_lat,max_lon,max_lat
    const viewbox = '-125.0,24.0,-66.0,49.0'; // Rough USA bounds
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery)}&limit=5&viewbox=${viewbox}&bounded=0&countrycodes=us`;
    
    fetch(url, {
        headers: {
            'User-Agent': 'Slippy Road Conditions App'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                // For zip codes, prefer results that are actually postal codes
                let result = data[0];
                if (isZipCode) {
                    // Try to find a result with type "postcode"
                    const postcodeResult = data.find(r => r.type === 'postcode' || r.class === 'place');
                    if (postcodeResult) {
                        result = postcodeResult;
                    }
                }
                
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                
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
                }).addTo(map).bindPopup('Searched Location');
                
                userLocation = { lat, lng };
                loadReports(lat, lng);
            } else {
                alert('Location not found. Please try a different search term or include the city/state.');
            }
        })
        .catch(error => {
            console.error('Geocoding error:', error);
            alert('Error searching for location. Please try again.');
        });
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
        const timeAgo = getTimeAgo(new Date(report.created_at));
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
                <div class="report-intersection" id="intersection-${report.id}">📍 Loading location...</div>
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
    
    // Load intersections for all reports (with delay to respect Nominatim rate limits)
    // Nominatim requires max 1 request per second, so we delay by 1.1 seconds per request
    reports.forEach((report, index) => {
        setTimeout(() => {
            loadIntersection(report.id, report.latitude, report.longitude);
        }, index * 1100); // 1.1 seconds between requests
    });
    
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
        const timeAgo = getTimeAgo(new Date(report.created_at));
        
        marker.bindPopup(`
            <strong>${conditionLabel}</strong><br>
            <small>📍 ${locationLabel}</small><br>
            ${submitterInfo}<br>
            <small>${timeAgo}</small>
        `);
    });
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

// Load intersection for a report
function loadIntersection(reportId, lat, lng) {
    fetch('api/get_intersection.php?lat=${lat}&lng=${lng}')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON');
            }
            return response.json();
        })
        .then(data => {
            const intersectionEl = document.getElementById(`intersection-${reportId}`);
            if (intersectionEl) {
                if (data.success && data.intersection) {
                    intersectionEl.textContent = `📍 ${data.intersection}`;
                } else {
                    // If we have an error message, log it for debugging
                    if (data.error) {
                        console.warn(`Intersection error for report ${reportId}:`, data.error);
                    }
                    intersectionEl.textContent = '📍 Location unavailable';
                }
            }
        })
        .catch(error => {
            console.error('Error loading intersection:', error);
            const intersectionEl = document.getElementById(`intersection-${reportId}`);
            if (intersectionEl) {
                intersectionEl.textContent = '📍 Location unavailable';
            }
        });
}

// Get time ago string
function getTimeAgo(date) {
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

// Handle form submission
document.getElementById('report-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!reportMarker || !selectedCondition) {
        alert('Please select a condition type and place a pin on the map.');
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
                throw new Error(data.error || `HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Report submitted successfully!');
            
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
            alert('Error: ' + (data.error || 'Failed to submit report'));
        }
    })
    .catch(error => {
        console.error('Submission error:', error);
        alert('Error submitting report: ' + error.message + '\n\nCheck the browser console for more details.');
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
    fetch('api/get_comments.php?report_id=${reportId}')
        .then(response => response.json())
        .then(data => {
            const commentsList = document.getElementById('comments-list');
            if (data.comments && data.comments.length > 0) {
                commentsList.innerHTML = data.comments.map(comment => {
                    const timeAgo = getTimeAgo(new Date(comment.created_at));
                    return `
                        <div class="comment-item">
                            <div class="comment-text">${escapeHtml(comment.comment_text)}</div>
                            <div class="comment-time">${timeAgo}</div>
                        </div>
                    `;
                }).join('');
            } else {
                commentsList.innerHTML = '<p class="loading">No comments yet. Be the first to comment!</p>';
            }
        })
        .catch(error => {
            console.error('Error loading comments:', error);
            document.getElementById('comments-list').innerHTML = '<p class="loading">Error loading comments.</p>';
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
            
            // Reload comments
            loadComments(reportId);
            
            // Update comment count in report list
            const commentBtn = document.querySelector(`.comment-btn[data-report-id="${reportId}"]`);
            if (commentBtn) {
                // Reload all reports to get updated counts
                if (userLocation) {
                    loadReports(userLocation.lat, userLocation.lng);
                } else {
                    loadReports();
                }
            }
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

