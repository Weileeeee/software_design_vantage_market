<?php
// =============================================================
// VantageMarket — Observer Pattern Integration Smoke Test
// Tests autoloader, class structure, and observer wiring.
// Does NOT require a live MySQL connection.
// Run: php test_observer.php
// =============================================================

declare(strict_types=1);

// ---- PSR-4 autoloader (same as bootstrap.php) ---------------
spl_autoload_register(function (string $class): void {
    $prefix = 'VantageMarket\\';
    $base   = __DIR__ . '/src/';
    if (!str_starts_with($class, $prefix)) return;
    $file = $base . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) require_once $file;
});

use VantageMarket\Observer\StockSubjectInterface;
use VantageMarket\Observer\StockObserverInterface;
use VantageMarket\Models\Product;
use VantageMarket\Models\Cart;
use VantageMarket\Models\User;

$pass = 0;
$fail = 0;

function ok(string $label, bool $result): void {
    global $pass, $fail;
    if ($result) {
        echo "  ✅  $label\n";
        $pass++;
    } else {
        echo "  ❌  FAIL: $label\n";
        $fail++;
    }
}

echo "\n=== VantageMarket Observer Pattern — Smoke Test ===\n\n";

// ----------------------------------------------------------
// 1. Interfaces exist and are loadable
// ----------------------------------------------------------
echo "[ 1 ] Interface loading\n";
ok('StockSubjectInterface is an interface',  interface_exists(StockSubjectInterface::class));
ok('StockObserverInterface is an interface', interface_exists(StockObserverInterface::class));

// ----------------------------------------------------------
// 2. Interface method signatures are correct
// ----------------------------------------------------------
echo "\n[ 2 ] StockSubjectInterface method signatures\n";
$subjectRef = new ReflectionClass(StockSubjectInterface::class);
ok('attach()  exists on Subject',  $subjectRef->hasMethod('attach'));
ok('detach()  exists on Subject',  $subjectRef->hasMethod('detach'));
ok('notify()  exists on Subject',  $subjectRef->hasMethod('notify'));

$attachParams = $subjectRef->getMethod('attach')->getParameters();
ok('attach() takes productId as 1st param', ($attachParams[0]->getName() === 'productId'));
ok('attach() takes cartId    as 2nd param', ($attachParams[1]->getName() === 'cartId'));

$notifyParams = $subjectRef->getMethod('notify')->getParameters();
ok('notify() takes productId as 1st param', ($notifyParams[0]->getName() === 'productId'));
ok('notify() takes newStock  as 2nd param', ($notifyParams[1]->getName() === 'newStock'));

echo "\n[ 3 ] StockObserverInterface method signatures\n";
$observerRef = new ReflectionClass(StockObserverInterface::class);
ok('update() exists on Observer', $observerRef->hasMethod('update'));
$updateParams = $observerRef->getMethod('update')->getParameters();
ok('update() takes productId as 1st param', ($updateParams[0]->getName() === 'productId'));
ok('update() takes newStock  as 2nd param', ($updateParams[1]->getName() === 'newStock'));

// ----------------------------------------------------------
// 3. Model value objects
// ----------------------------------------------------------
echo "\n[ 4 ] Product model\n";
$product = new Product(
    productId: 1, categoryId: 2, title: 'Mechanical Keyboard',
    description: 'RGB keyboard', price: 299.90, stockLevel: 0,
    brand: 'KeyTech', sku: 'KB-104', status: 'active'
);
ok('Product instantiates correctly',       $product->productId === 1);
ok('Product->isOutOfStock() true at 0',    $product->isOutOfStock() === true);

$productInStock = new Product(
    productId: 2, categoryId: 2, title: 'USB-C Hub',
    description: null, price: 49.90, stockLevel: 5,
    brand: 'NexHub', sku: 'HUB-07'
);
ok('Product->isOutOfStock() false at 5',   $productInStock->isOutOfStock() === false);
ok('Product->toPublicArray() has sku key', array_key_exists('sku', $product->toPublicArray()));

echo "\n[ 5 ] Cart model\n";
$cart = new Cart(cartId: 10, userId: null, sessionId: 'abc123');
ok('Cart instantiates correctly',         $cart->cartId === 10);
ok('Cart->isGuestCart() true (no userId)', $cart->isGuestCart() === true);

$authCart = new Cart(cartId: 11, userId: 5, sessionId: null);
ok('Cart->isGuestCart() false (userId=5)', $authCart->isGuestCart() === false);
ok('Cart->toPublicArray() has cart_id key', array_key_exists('cart_id', $cart->toPublicArray()));

// ----------------------------------------------------------
// 4. Concrete classes implement their interfaces
// ----------------------------------------------------------
echo "\n[ 6 ] Class → interface implementation\n";

// CartObserver implements StockObserverInterface
$cartObserverRef = new ReflectionClass(\VantageMarket\Services\CartObserver::class);
ok('CartObserver implements StockObserverInterface',
    $cartObserverRef->implementsInterface(StockObserverInterface::class));
ok('CartObserver::update() is public',
    $cartObserverRef->getMethod('update')->isPublic());

// ProductStockSubject implements StockSubjectInterface
$subjectConcreteRef = new ReflectionClass(\VantageMarket\Services\ProductStockSubject::class);
ok('ProductStockSubject implements StockSubjectInterface',
    $subjectConcreteRef->implementsInterface(StockSubjectInterface::class));
ok('ProductStockSubject::attach() is public',  $subjectConcreteRef->getMethod('attach')->isPublic());
ok('ProductStockSubject::detach() is public',  $subjectConcreteRef->getMethod('detach')->isPublic());
ok('ProductStockSubject::notify() is public',  $subjectConcreteRef->getMethod('notify')->isPublic());
ok('ProductStockSubject::decrementAndNotify() exists',
    $subjectConcreteRef->hasMethod('decrementAndNotify'));

// ----------------------------------------------------------
// 5. Repository classes exist and have expected methods
// ----------------------------------------------------------
echo "\n[ 7 ] Repository method existence\n";

$soRepoRef = new ReflectionClass(\VantageMarket\Services\StockObserverRepository::class);
ok('StockObserverRepository::register()      exists', $soRepoRef->hasMethod('register'));
ok('StockObserverRepository::deregister()    exists', $soRepoRef->hasMethod('deregister'));
ok('StockObserverRepository::deregisterAll() exists', $soRepoRef->hasMethod('deregisterAll'));
ok('StockObserverRepository::deregisterCart() exists',$soRepoRef->hasMethod('deregisterCart'));
ok('StockObserverRepository::findCartsByProduct() exists', $soRepoRef->hasMethod('findCartsByProduct'));
ok('StockObserverRepository::isRegistered() exists', $soRepoRef->hasMethod('isRegistered'));

$cartRepoRef = new ReflectionClass(\VantageMarket\Services\CartRepository::class);
ok('CartRepository::findOrCreateForUser()    exists', $cartRepoRef->hasMethod('findOrCreateForUser'));
ok('CartRepository::findOrCreateForSession() exists', $cartRepoRef->hasMethod('findOrCreateForSession'));
ok('CartRepository::addItem()                exists', $cartRepoRef->hasMethod('addItem'));
ok('CartRepository::removeItem()             exists', $cartRepoRef->hasMethod('removeItem'));
ok('CartRepository::updateItemQuantity()     exists', $cartRepoRef->hasMethod('updateItemQuantity'));
ok('CartRepository::getItems()               exists', $cartRepoRef->hasMethod('getItems'));
ok('CartRepository::clearCart()              exists', $cartRepoRef->hasMethod('clearCart'));

$prodRepoRef = new ReflectionClass(\VantageMarket\Services\ProductRepository::class);
ok('ProductRepository::findById()            exists', $prodRepoRef->hasMethod('findById'));
ok('ProductRepository::findByCartId()        exists', $prodRepoRef->hasMethod('findByCartId'));
ok('ProductRepository::findAllActive()       exists', $prodRepoRef->hasMethod('findAllActive'));
ok('ProductRepository::updateStock()         exists', $prodRepoRef->hasMethod('updateStock'));
ok('ProductRepository::decrementStock()      exists', $prodRepoRef->hasMethod('decrementStock'));

// ----------------------------------------------------------
// 6. Observer wiring simulation (no DB — mock objects)
// ----------------------------------------------------------
echo "\n[ 8 ] Observer wiring simulation (mock)\n";

/**
 * Mock StockObserverRepository — records calls without touching DB.
 */
class MockObserverRepo extends \VantageMarket\Services\StockObserverRepository {
    public array $registered   = [];
    public array $deregistered = [];
    public array $deregAll     = [];
    public array $cartsByProduct = [];

    public function __construct() { /* skip DB */ }

    public function register(int $productId, int $cartId): void {
        $this->registered[] = [$productId, $cartId];
    }
    public function deregister(int $productId, int $cartId): void {
        $this->deregistered[] = [$productId, $cartId];
    }
    public function deregisterAll(int $productId): void {
        $this->deregAll[] = $productId;
    }
    public function findCartsByProduct(int $productId): array {
        return $this->cartsByProduct[$productId] ?? [];
    }
}

/**
 * Mock CartRepository — records removeItem calls without touching DB.
 */
class MockCartRepo extends \VantageMarket\Services\CartRepository {
    public array $removed = [];

    public function __construct() { /* skip DB */ }

    public function removeItem(int $cartId, int $productId): void {
        $this->removed[] = [$cartId, $productId];
    }
}

$mockObserverRepo = new MockObserverRepo();
$mockCartRepo     = new MockCartRepo();

// Pre-seed: carts 10, 11, 12 are watching product 1
$mockObserverRepo->cartsByProduct[1] = [10, 11, 12];

$cartObserver = new \VantageMarket\Services\CartObserver($mockCartRepo, $mockObserverRepo);
$stockSubject = new \VantageMarket\Services\ProductStockSubject($mockObserverRepo, $cartObserver);

// Test attach / detach
$stockSubject->attach(1, 10);
$stockSubject->attach(1, 11);
$stockSubject->attach(1, 12);
ok('attach() registers 3 carts (calls register)',
    count($mockObserverRepo->registered) === 3);

$stockSubject->detach(1, 99);
ok('detach() calls deregister once',
    count($mockObserverRepo->deregistered) === 1);

// Test notify with stock > 0 (no action expected)
$stockSubject->notify(1, 5);
ok('notify(stock=5) does NOT remove any cart item',  count($mockCartRepo->removed) === 0);
ok('notify(stock=5) does NOT deregisterAll',         count($mockObserverRepo->deregAll) === 0);

// Test notify with stock = 0 (should remove items from all 3 carts)
$stockSubject->notify(1, 0);
ok('notify(stock=0) removes item from 3 carts',     count($mockCartRepo->removed) === 3);
ok('notify(stock=0) removes product 1 from cart 10', in_array([10, 1], $mockCartRepo->removed));
ok('notify(stock=0) removes product 1 from cart 11', in_array([11, 1], $mockCartRepo->removed));
ok('notify(stock=0) removes product 1 from cart 12', in_array([12, 1], $mockCartRepo->removed));
ok('notify(stock=0) calls deregisterAll for product 1',
    in_array(1, $mockObserverRepo->deregAll));

// ----------------------------------------------------------
// Summary
// ----------------------------------------------------------
$total = $pass + $fail;
echo "\n===================================================\n";
echo "  Result: {$pass}/{$total} tests passed";
if ($fail > 0) {
    echo "  ({$fail} FAILED)";
}
echo "\n===================================================\n\n";
exit($fail > 0 ? 1 : 0);
