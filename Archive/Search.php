<?php
// search.php

// Enable PHP error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection parameters
$servername = "proj-mysql.uopnet.plymouth.ac.uk";
$db_username = "comp2003_5";
$db_password = "GcvN732+";
$dbname = "comp2003_5";

// Create a new database connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check for a connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve and sanitize the search query
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Retrieve and sanitize additional filters
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : '';

// Pagination Parameters
$results_per_page = 10; // Number of items per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$start_from = ($page - 1) * $results_per_page;

// Function to highlight search terms
function highlight_terms($text, $terms) {
    foreach ($terms as $term) {
        if (!empty($term)) {
            // Use word boundaries to match whole words only
            $text = preg_replace("/(" . preg_quote($term, '/') . ")/i", '<span class="highlight">$1</span>', $text);
        }
    }
    return $text;
}

// Initialize variables
$items = [];
$total_records = 0;
$total_pages = 0;

// Check if a search query is provided
if (!empty($search_query)) {
    // Build the SQL query dynamically based on filters
    $sql = "SELECT * FROM items WHERE (name LIKE CONCAT('%', ?, '%') OR description LIKE CONCAT('%', ?, '%'))";
    
    $params = [$search_query, $search_query];
    $types = "ss";
    
    if (!empty($category)) {
        $sql .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    if ($min_price > 0) {
        $sql .= " AND price >= ?";
        $params[] = $min_price;
        $types .= "d";
    }
    
    if ($max_price > 0) {
        $sql .= " AND price <= ?";
        $params[] = $max_price;
        $types .= "d";
    }
    
    // Append ORDER BY clause based on sort parameter
    switch ($sort) {
        case 'price_asc':
            $sql .= " ORDER BY price ASC";
            break;
        case 'price_desc':
            $sql .= " ORDER BY price DESC";
            break;
        case 'newest':
            $sql .= " ORDER BY created_at DESC";
            break;
        case 'oldest':
            $sql .= " ORDER BY created_at ASC";
            break;
        default:
            $sql .= " ORDER BY name ASC"; // Default ordering
            break;
    }
    
    // Append LIMIT for pagination
    $sql .= " LIMIT ?, ?";
    
    $params[] = $start_from;
    $params[] = $results_per_page;
    $types .= "ii";
    
    // Prepare and bind the statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    
    // Execute the statement
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();
    
    // Fetch all matching items
    $items = $result->fetch_all(MYSQLI_ASSOC);
    
    // Close the statement
    $stmt->close();
    
    // Calculate total number of results for pagination
    $total_sql = "SELECT COUNT(*) FROM items WHERE (name LIKE CONCAT('%', ?, '%') OR description LIKE CONCAT('%', ?, '%'))";
    
    $total_params = [$search_query, $search_query];
    $total_types = "ss";
    
    if (!empty($category)) {
        $total_sql .= " AND category = ?";
        $total_params[] = $category;
        $total_types .= "s";
    }
    
    if ($min_price > 0) {
        $total_sql .= " AND price >= ?";
        $total_params[] = $min_price;
        $total_types .= "d";
    }
    
    if ($max_price > 0) {
        $total_sql .= " AND price <= ?";
        $total_params[] = $max_price;
        $total_types .= "d";
    }
    
    $total_stmt = $conn->prepare($total_sql);
    if ($total_stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    
    $total_stmt->bind_param($total_types, ...$total_params);
    $total_stmt->execute();
    $total_stmt->bind_result($total_records);
    $total_stmt->fetch();
    $total_stmt->close();
    
    $total_pages = ceil($total_records / $results_per_page);
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Items</title>
    <link rel="stylesheet" href="styles/Search.css">
</head>
<body>
    <!-- Loading Indicator -->
    <div id="loading" class="hidden">Loading...</div>

    <!-- Search Form -->
    <form action="search.php" method="GET" id="search-form">
        <input type="text" name="query" placeholder="Search for items..." value="<?php echo htmlspecialchars($search_query); ?>" required>

        <!-- Category Filter -->
        <select name="category">
            <option value="">All Categories</option>
            <?php
                // Fetch categories from the database
                $conn = new mysqli($servername, $db_username, $db_password, $dbname);
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }
                $category_sql = "SELECT name FROM categories ORDER BY name ASC";
                $category_result = $conn->query($category_sql);
                if ($category_result->num_rows > 0) {
                    while ($category_row = $category_result->fetch_assoc()):
            ?>
                <option value="<?php echo htmlspecialchars($category_row['name']); ?>" <?php if ($category_row['name'] == $category) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($category_row['name']); ?>
                </option>
            <?php
                    endwhile;
                }
                $conn->close();
            ?>
        </select>

        <!-- Price Range Filter -->
        <input type="number" name="min_price" placeholder="Min Price (£)" step="0.01" min="0" value="<?php echo htmlspecialchars($min_price > 0 ? number_format($min_price, 2) : ''); ?>">
        <input type="number" name="max_price" placeholder="Max Price (£)" step="0.01" min="0" value="<?php echo htmlspecialchars($max_price > 0 ? number_format($max_price, 2) : ''); ?>">

        <!-- Sorting Options -->
        <select name="sort" id="sort">
            <option value="">Sort By</option>
            <option value="price_asc" <?php if ($sort == 'price_asc') echo 'selected'; ?>>Price: Low to High</option>
            <option value="price_desc" <?php if ($sort == 'price_desc') echo 'selected'; ?>>Price: High to Low</option>
            <option value="newest" <?php if ($sort == 'newest') echo 'selected'; ?>>Newest First</option>
            <option value="oldest" <?php if ($sort == 'oldest') echo 'selected'; ?>>Oldest First</option>
        </select>

        <button type="submit">Search</button>
    </form>

    <!-- Applied Filters Display -->
    <?php if (!empty($search_query)): ?>
        <?php if (!empty($category) || $min_price > 0 || $max_price > 0 || !empty($sort)): ?>
            <div class="applied-filters">
                <h3>Applied Filters:</h3>
                <ul>
                    <?php if (!empty($category)): ?>
                        <li>Category: <?php echo htmlspecialchars($category); ?></li>
                    <?php endif; ?>
                    <?php if ($min_price > 0): ?>
                        <li>Min Price: £<?php echo htmlspecialchars(number_format($min_price, 2)); ?></li>
                    <?php endif; ?>
                    <?php if ($max_price > 0): ?>
                        <li>Max Price: £<?php echo htmlspecialchars(number_format($max_price, 2)); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($sort)): ?>
                        <li>Sort By: 
                            <?php
                                switch ($sort) {
                                    case 'price_asc':
                                        echo "Price: Low to High";
                                        break;
                                    case 'price_desc':
                                        echo "Price: High to Low";
                                        break;
                                    case 'newest':
                                        echo "Newest First";
                                        break;
                                    case 'oldest':
                                        echo "Oldest First";
                                        break;
                                    default:
                                        echo "Default";
                                        break;
                                }
                            ?>
                        </li>
                    <?php endif; ?>
                </ul>
                <a href="search.php?query=<?php echo urlencode($search_query); ?>" class="clear-filters">Clear Filters</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Search Results -->
    <?php if (!empty($search_query)): ?>
        <?php if (count($items) > 0): ?>
            <div class="items-list">
                <?php foreach ($items as $item): ?>
                    <div class="item-card">
                        <?php if (file_exists($item['image'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php else: ?>
                            <img src="uploads/placeholder.png" alt="No image available">
                        <?php endif; ?>
                        <h3><?php echo highlight_terms(htmlspecialchars($item['name']), explode(' ', $search_query)); ?></h3>
                        <p>Price: £<?php echo htmlspecialchars(number_format($item['price'], 2)); ?></p>
                        <p><?php echo highlight_terms(htmlspecialchars(substr($item['description'], 0, 100)), explode(' ', $search_query)); ?>...</p>
                        <a href="ItemDetails.php?id=<?php echo htmlspecialchars($item['id']); ?>" class="details-button">View Details</a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination Links -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                        // Generate the base URL for pagination links
                        $base_url = 'search.php?query=' . urlencode($search_query);
                        if (!empty($category)) {
                            $base_url .= '&category=' . urlencode($category);
                        }
                        if ($min_price > 0) {
                            $base_url .= '&min_price=' . urlencode($min_price);
                        }
                        if ($max_price > 0) {
                            $base_url .= '&max_price=' . urlencode($max_price);
                        }
                        if (!empty($sort)) {
                            $base_url .= '&sort=' . urlencode($sort);
                        }
                    ?>

                    <?php if ($page > 1): ?>
                        <a href="<?php echo $base_url . '&page=' . ($page - 1); ?>">&laquo; Previous</a>
                    <?php endif; ?>

                    <?php
                        // Display a range of page numbers
                        $max_links = 5; // Maximum number of page links to display
                        $start_page = max(1, $page - floor($max_links / 2));
                        $end_page = min($total_pages, $start_page + $max_links - 1);

                        if ($start_page > 1) {
                            echo '<a href="' . $base_url . '&page=1">1</a>';
                            if ($start_page > 2) {
                                echo '<span>...</span>';
                            }
                        }

                        for ($i = $start_page; $i <= $end_page; $i++):
                            if ($i == $page):
                    ?>
                                <span class="current-page"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo $base_url . '&page=' . $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span>...</span>
                        <?php endif; ?>
                        <a href="<?php echo $base_url . '&page=' . $total_pages; ?>"><?php echo $total_pages; ?></a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo $base_url . '&page=' . ($page + 1); ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <p>No items found matching your search criteria.</p>
        <?php endif; ?>
    <?php endif; ?>

    <!-- JavaScript for Autocomplete/Suggestions -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('#search-form input[name="query"]');
            const suggestionsBox = document.createElement('div');
            suggestionsBox.classList.add('suggestions-box');
            searchInput.parentNode.appendChild(suggestionsBox);

            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                if (query.length < 2) {
                    suggestionsBox.innerHTML = '';
                    suggestionsBox.style.display = 'none';
                    return;
                }

                // Show loading
                document.getElementById('loading').classList.add('show');

                fetch(`suggestions.php?term=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('loading').classList.remove('show');
                        suggestionsBox.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(item => {
                                const suggestion = document.createElement('div');
                                suggestion.classList.add('suggestion-item');
                                suggestion.textContent = item;
                                suggestion.addEventListener('click', function() {
                                    searchInput.value = this.textContent;
                                    suggestionsBox.innerHTML = '';
                                    suggestionsBox.style.display = 'none';
                                });
                                suggestionsBox.appendChild(suggestion);
                            });
                            suggestionsBox.style.display = 'block';
                        } else {
                            suggestionsBox.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching suggestions:', error);
                        document.getElementById('loading').classList.remove('show');
                        suggestionsBox.style.display = 'none';
                    });
            });

            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                    suggestionsBox.innerHTML = '';
                    suggestionsBox.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
