<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vantage Market - Product Catalogue</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            /* Deep slate midnight background */
            color: #f1f5f9;
            /* Off-white text for excellent contrast */
        }

        /* Sidebar Styling */
        .filter-card {
            background: #1e293b;
            /* Sleek dark panel color */
            border-radius: 16px;
            border: 1px solid #334155;
        }

        .filter-title {
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #94a3b8;
        }

        .form-control-custom {
            background-color: #0f172a;
            border-radius: 10px;
            border: 1px solid #475569;
            color: #f1f5f9;
            padding: 0.6rem 0.8rem;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .form-control-custom:focus {
            background-color: #0f172a;
            color: #f1f5f9;
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.25);
        }

        .form-control-custom::placeholder {
            color: #64748b;
        }

        /* Checkboxes on Dark Background */
        .form-check-input {
            background-color: #0f172a;
            border-color: #475569;
        }

        .form-check-input:checked {
            background-color: #6366f1;
            border-color: #6366f1;
        }

        /* Product Card Grid Styling */
        .product-card {
            background: #1e293b;
            border-radius: 16px;
            border: 1px solid #334155;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
            border-color: #4f46e5;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }

        .img-container {
            height: 200px;
            background: #0f172a;
            border-radius: 12px 12px 0 0;
            position: relative;
            border-bottom: 1px solid #334155;
        }

        /* Modern Badges */
        .badge-category {
            background-color: rgba(99, 102, 241, 0.15);
            color: #818cf8;
            /* Vibrant light purple */
            font-weight: 600;
            font-size: 0.75rem;
            padding: 0.35em 0.75em;
            border-radius: 6px;
            border: 1px solid rgba(129, 140, 248, 0.3);
        }

        .badge-stock {
            font-size: 0.75rem;
            color: #94a3b8;
            background: #0f172a;
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            border: 1px solid #334155;
        }

        /* Custom Range Slider */
        .form-range::-webkit-slider-runnable-track {
            background-color: #334155;
        }

        .form-range::-webkit-slider-thumb {
            background: #818cf8;
        }

        .form-range::-moz-range-thumb {
            background: #818cf8;
        }

        /* Buttons styling */
        .btn-prime {
            background: #6366f1;
            color: #ffffff;
            border: none;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.2s;
        }

        .btn-prime:hover {
            background: #4f46e5;
            color: #ffffff;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.4);
        }

        .btn-add-cart {
            background: #0f172a;
            color: #cbd5e1;
            border: 1px solid #475569;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.2s;
        }

        .btn-add-cart:hover {
            background: #6366f1;
            color: #ffffff;
            border-color: #6366f1;
            box-shadow: 0 0 12px rgba(99, 102, 241, 0.3);
        }

        .text-link-custom {
            color: #94a3b8;
            transition: color 0.2s;
        }

        .text-link-custom:hover {
            color: #818cf8;
        }
    </style>
</head>

<body>

    <header class="py-4 mb-5 bg-dark border-bottom" style="background-color: #1e293b !important; border-color: #334155 !important;">
        <div class="container d-flex justify-content-between align-items-center">
            <h4 class="fw-bold mb-0 text-white">Vantage<span style="color: #818cf8;">Market</span></h4>
            <span class="text-muted small">Explore Premium Tech Items</span>
        </div>
    </header>

    <div class="container">
        <div class="row">

            <aside class="col-md-3 mb-4">
                <div class="card filter-card p-4 shadow">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold m-0 text-white">Filters</h5>
                        <a href="/catalog" class="text-decoration-none small text-link-custom">Clear All</a>
                    </div>

                    <form action="/catalog" method="GET">

                        <div class="mb-4">
                            <label class="form-label filter-title fw-bold text-uppercase">Search</label>
                            <input type="text" name="search" class="form-control form-control-custom" placeholder="Type keywords..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label filter-title fw-bold text-uppercase mb-3">Categories</label>

                            <div class="form-check mb-25 py-1">
                                <input class="form-check-input" type="checkbox" name="category[]" value="1" id="cat-1" <?= in_array('1', $_GET['category'] ?? []) ? 'checked' : '' ?>>
                                <label class="form-check-label small ms-1 text-secondary" style="color: #cbd5e1 !important;" for="cat-1">Audio & Earbuds</label>
                            </div>

                            <div class="form-check mb-25 py-1">
                                <input class="form-check-input" type="checkbox" name="category[]" value="2" id="cat-2" <?= in_array('2', $_GET['category'] ?? []) ? 'checked' : '' ?>>
                                <label class="form-check-label small ms-1 text-secondary" style="color: #cbd5e1 !important;" for="cat-2">Computer Peripherals</label>
                            </div>

                            <div class="form-check mb-25 py-1">
                                <input class="form-check-input" type="checkbox" name="category[]" value="3" id="cat-3" <?= in_array('3', $_GET['category'] ?? []) ? 'checked' : '' ?>>
                                <label class="form-check-label small ms-1 text-secondary" style="color: #cbd5e1 !important;" for="cat-3">Desk Accessories</label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <?php $current_max = htmlspecialchars($_GET['max_price'] ?? '500'); ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label filter-title fw-bold text-uppercase m-0">Max Budget</label>
                                <span class="fw-bold text-white" style="font-size: 0.95rem;">$<span id="priceValue"><?= $current_max ?></span></span>
                            </div>
                            <input type="range" class="form-range" min="0" max="500" step="10" id="priceRange" name="max_price" value="<?= $current_max ?>" oninput="document.getElementById('priceValue').innerText = this.value">
                        </div>

                        <button type="submit" class="btn btn-prime w-100 py-2.5 mt-2 shadow">Apply Filters</button>
                    </form>

                </div>
            </aside>

            <main class="col-md-9">
                <div class="row row-cols-1 row-cols-md-3 g-4">

                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="col">
                                <div class="card h-100 product-card border-0 shadow">

                                    <div class="img-container d-flex align-items-center justify-content-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" stroke="#475569" stroke-width="1.5" class="bi bi-box-seam" viewBox="0 0 16 16">
                                            <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464L7.443.184z" />
                                        </svg>
                                    </div>

                                    <div class="card-body d-flex flex-column justify-content-between p-4">
                                        <div>
                                            <div class="mb-2">
                                                <?php
                                                $resolvedName = $product['category_name']
                                                    ?? $product['name']
                                                    ?? $product['title']
                                                    ?? 'General Product';
                                                ?>
                                                <span class="badge badge-category">
                                                    <?= htmlspecialchars((string)$resolvedName) ?>
                                                </span>
                                            </div>

                                            <h5 class="card-title fw-bold text-white m-0 mb-2" style="font-size: 1.05rem; line-height: 1.4;">
                                                <?= htmlspecialchars($product['title']) ?>
                                            </h5>

                                            <p class="card-text text-muted small mb-4" style="color: #94a3b8 !important; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.6;">
                                                <?= htmlspecialchars($product['description'] ?? 'No description provided.') ?>
                                            </p>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2" style="border-top: 1px solid #334155;">
                                            <span class="fw-bold text-white fs-5">$<?= number_format((float)$product['price'], 2) ?></span>
                                            <span class="badge-stock small fw-medium">In Stock: <?= htmlspecialchars((string)$product['stock_level']) ?></span>
                                        </div>
                                    </div>

                                    <div class="card-footer bg-transparent border-0 p-4 pt-0">
                                        <button class="btn btn-add-cart w-100 py-2 btn-sm fw-semibold shadow">Add to Cart</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5 my-5">
                            <div class="mb-3" style="color: #475569;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1" class="bi bi-search" viewBox="0 0 16 16">
                                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" />
                                </svg>
                            </div>
                            <h5 class="text-white fw-bold">No Match Found</h5>
                            <p class="text-muted small" style="color: #64748b !important;">Try refining your keyword query combinations or resetting filters.</p>
                            <a href="/catalog" class="btn btn-sm btn-outline-secondary px-4 mt-2 rounded-pill" style="border-color: #475569; color: #94a3b8;">Reset All Filters</a>
                        </div>
                    <?php endif; ?>

                </div>
            </main>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>