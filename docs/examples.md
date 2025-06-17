# Usage Examples

This document provides comprehensive examples of how the Yii2 PHPStan extension enhances type inference and static 
analysis in real-world scenarios.

## ActiveRecord Examples

### Basic CRUD Operations

```php
<?php

use app\models\User;
use yii\db\ActiveRecord;

class UserService
{
    public function getUserById(int $id): User|null
    {
        // ✅ PHPStan knows this returns User|null
        return User::findOne($id);
    }
    
    public function getAllActiveUsers(): array
    {
        // ✅ PHPStan knows this returns User[]
        return User::findAll(['status' => 'active']);
    }
    
    public function getUsersAsArray(): array
    {
        // ✅ PHPStan knows this returns array<int, array{id: int, name: string, email: string}>
        return User::find()->asArray()->all();
    }
    
    public function createUser(array $attributes): User
    {
        $user = new User();
        $user->setAttributes($attributes);
        
        if ($user->save()) {
            // ✅ PHPStan knows $user is User type
            return $user;
        }
        
        throw new \RuntimeException('Failed to create user');
    }
}
```

### Complex Queries with Method Chaining

```php
<?php

use app\models\{User, Post};
use yii\db\ActiveQuery;

class PostRepository
{
    public function getPublishedPosts(): array
    {
        // ✅ PHPStan tracks the generic type through the chain
        $query = Post::find()
            ->where(['status' => 'published'])
            ->andWhere(['>', 'published_at', time() - 86400])
            ->orderBy('published_at DESC')
            ->limit(10);
            
        // ✅ Returns Post[]
        return $query->all();
    }
    
    public function getPostsAsArrayWithAuthor(): array
    {
        // ✅ PHPStan knows this returns array<int, array{...}>
        return Post::find()
            ->joinWith('author')
            ->asArray()
            ->all();
    }
    
    public function getLatestPost(): Post|null
    {
        // ✅ PHPStan knows this returns Post|null
        return Post::find()
            ->where(['status' => 'published'])
            ->orderBy('created_at DESC')
            ->one();
    }
    
    public function getPostsByAuthor(User $author): ActiveQuery
    {
        // ✅ Return type is properly inferred as ActiveQuery<Post>
        return Post::find()->where(['author_id' => $author->id]);
    }
}
```

### Relations and Eager Loading

```php
<?php

use app\models\{User, Post, Category};

class UserModel extends \yii\db\ActiveRecord
{
    public function getPosts(): \yii\db\ActiveQuery
    {
        // ✅ PHPStan knows this returns ActiveQuery<Post>
        return $this->hasMany(Post::class, ['author_id' => 'id']);
    }
    
    public function getProfile(): \yii\db\ActiveQuery
    {
        // ✅ PHPStan knows this returns ActiveQuery<UserProfile>
        return $this->hasOne(UserProfile::class, ['user_id' => 'id']);
    }
}

class PostService
{
    public function getUserWithPosts(int $userId): User|null
    {
        // ✅ PHPStan tracks eager loading
        $user = User::find()
            ->with('posts', 'profile')
            ->where(['id' => $userId])
            ->one();
            
        if ($user !== null) {
            // ✅ PHPStan knows $user->posts is Post[]
            foreach ($user->posts as $post) {
                // ✅ $post is properly typed as Post
                echo $post->title;
            }
            
            // ✅ PHPStan knows $user->profile is UserProfile|null
            if ($user->profile !== null) {
                echo $user->profile->bio;
            }
        }
        
        return $user;
    }
    
    public function getPostsWithCategories(): array
    {
        // ✅ PHPStan knows the result structure
        return Post::find()
            ->joinWith('category')
            ->asArray()
            ->all();
    }
}
```

### Custom ActiveQuery Classes

```php
<?php

use yii\db\ActiveQuery;

class PostQuery extends ActiveQuery
{
    public function published(): self
    {
        return $this->andWhere(['status' => 'published']);
    }
    
    public function byCategory(string $categorySlug): self
    {
        return $this->joinWith('category')
            ->andWhere(['category.slug' => $categorySlug]);
    }
    
    public function recent(): self
    {
        return $this->orderBy('created_at DESC');
    }
}

class Post extends \yii\db\ActiveRecord
{
    public static function find(): PostQuery
    {
        // ✅ PHPStan knows this returns PostQuery<Post>
        return new PostQuery(get_called_class());
    }
}

class PostController
{
    public function actionIndex(): string
    {
        // ✅ Method chaining works with custom query classes
        $posts = Post::find()
            ->published()
            ->byCategory('technology')
            ->recent()
            ->limit(10)
            ->all(); // ✅ Returns Post[]
            
        return $this->render('index', ['posts' => $posts]);
    }
    
    public function actionAsArray(): array
    {
        // ✅ Array results are properly typed
        return Post::find()
            ->published()
            ->asArray()
            ->all(); // ✅ Returns array<int, array{...}>
    }
}
```

## Application Component Examples

### Built-in Components

```php
<?php

use Yii;
use yii\web\Controller;
use yii\mail\MessageInterface;

class SiteController extends Controller
{
    public function actionLogin(): string
    {
        // ✅ PHPStan knows the component types
        $request = Yii::$app->request;    // Request
        $response = Yii::$app->response;  // Response
        $session = Yii::$app->session;    // Session
        $user = Yii::$app->user;          // User
        
        if ($request->isPost) {
            $postData = $request->post();
            
            if ($user->login($identity)) {
                $session->setFlash('success', 'Login successful');
                return $this->goHome();
            }
        }
        
        return $this->render('login');
    }
    
    public function actionSendEmail(): bool
    {
        // ✅ PHPStan knows mailer interface
        $mailer = Yii::$app->mailer; // MailerInterface
        
        $message = $mailer->compose()
            ->setFrom('noreply@example.com')
            ->setTo('user@example.com')
            ->setSubject('Test Email')
            ->setTextBody('This is a test email');
            
        // ✅ PHPStan knows send() returns bool
        return $message->send();
    }
    
    public function actionDatabaseQuery(): array
    {
        // ✅ PHPStan knows db component type
        $db = Yii::$app->db; // Connection
        
        $command = $db->createCommand('SELECT * FROM users WHERE active = :active')->bindValue(':active', 1);
            
        // ✅ PHPStan knows queryAll() returns array
        return $command->queryAll();
    }
}
```

### User Component with Identity

```php
<?php

use Yii;
use app\models\User;

class UserService
{
    public function getCurrentUser(): User|null
    {
        if (Yii::$app->user->isGuest) {
            return null;
        }
        
        // ✅ PHPStan knows identity is User (from configuration)
        $identity = Yii::$app->user->identity; // User
        
        return $identity;
    }
    
    public function getUserId(): int|string|null
    {
        // ✅ PHPStan knows getId() returns int|string|null
        return Yii::$app->user->getId();
    }
    
    public function checkAccess(string $permission): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }
        
        // ✅ PHPStan knows the identity type
        $user = Yii::$app->user->identity;
        
        // ✅ Method calls are properly typed
        return $user->hasPermission($permission);
    }
    
    public function getUserPreferences(): array
    {
        $user = $this->getCurrentUser();
        
        if ($user === null) {
            return [];
        }
        
        // ✅ PHPStan tracks the User type through null checks
        return $user->getPreferences(); // Returns array
    }
}
```

### Custom Components

```php
<?php

// Component configuration in config/phpstan.php
return [
    'components' => [
        'paymentService' => [
            'class' => \app\services\PaymentService::class,
        ],
        'imageProcessor' => [
            'class' => \app\services\ImageProcessor::class,
        ],
    ],
];

// Usage in controllers
use Yii;
use yii\web\Controller;

class PaymentController extends Controller
{
    public function actionProcess(): array
    {
        // ✅ PHPStan knows this is PaymentService
        $paymentService = Yii::$app->paymentService; // PaymentService
        
        $result = $paymentService->processPayment(
            [
                'amount' => 100.00,
                'currency' => 'USD',
                'token' => $this->request->post('token'),
            ],
        );
        
        // ✅ PHPStan knows the return type based on method signature
        return $result; // array
    }
    
    public function actionProcessImage(): string
    {
        // ✅ PHPStan knows this is ImageProcessor
        $imageProcessor = Yii::$app->imageProcessor; // ImageProcessor
        
        $uploadedFile = \yii\web\UploadedFile::getInstanceByName('image');
        
        if ($uploadedFile !== null) {
            // ✅ Method calls are properly typed
            $processedPath = $imageProcessor->resize($uploadedFile->tempName, 800, 600);
            
            return $processedPath; // string
        }
        
        throw new \yii\web\BadRequestHttpException('No image uploaded');
    }
}
```

## Dependency Injection Container Examples

### Basic Service Resolution

```php
<?php

use yii\di\Container;
use app\services\{PaymentService, EmailService, CacheService};

class ServiceManager
{
    private Container $container;
    
    public function __construct()
    {
        $this->container = new Container();
    }
    
    public function getPaymentService(): PaymentService
    {
        // ✅ PHPStan knows this returns PaymentService
        return $this->container->get(PaymentService::class);
    }
    
    public function processOrder(array $orderData): bool
    {
        // ✅ Type-safe service resolution
        $paymentService = $this->container->get(PaymentService::class); // PaymentService
        $emailService = $this->container->get(EmailService::class);     // EmailService
        $cache = $this->container->get('cache');                        // CacheService (if configured)
        
        $paymentResult = $paymentService->charge($orderData['total']);
        
        if ($paymentResult->isSuccessful()) {
            $emailService->sendOrderConfirmation($orderData);
            $cache->delete("cart_{$orderData['user_id']}");
            
            return true;
        }
        
        return false;
    }
}
```

### Service Configuration Examples

```php
<?php
// config/phpstan.php - Container configuration

return [
    'container' => [
        'definitions' => [
            // Interface to implementation mapping
            \Psr\Log\LoggerInterface::class => \Monolog\Logger::class,
            
            // Service with configuration
            'logger' => [
                'class' => \Monolog\Logger::class,
            ],
            
            // Closure definition with return type hint
            'eventDispatcher' => function(): \app\services\EventDispatcher {
                return new \app\services\EventDispatcher();
            },
            
            // Service factory
            'cacheManager' => [
                'class' => \app\services\CacheManager::class,
            ],
        ],
        
        'singletons' => [
            // Singleton services
            \app\services\MetricsCollector::class => \app\services\MetricsCollector::class,
            
            'database' => [
                'class' => \app\services\DatabaseManager::class,
            ],
        ],
    ],
];

// Usage with proper type inference
class ApplicationService
{
    public function logActivity(string $message): void
    {
        $container = new Container();
        
        // ✅ PHPStan knows this is LoggerInterface
        $logger = $container->get(\Psr\Log\LoggerInterface::class);
        $logger->info($message);
        
        // ✅ PHPStan knows this is EventDispatcher
        $dispatcher = $container->get('eventDispatcher');
        $dispatcher->dispatch(new ActivityEvent($message));
    }
    
    public function getMetrics(): array
    {
        $container = new Container();
        
        // ✅ PHPStan knows this is MetricsCollector (singleton)
        $metrics = $container->get(\app\services\MetricsCollector::class);
        
        return $metrics->getAllMetrics(); // array
    }
}
```

### Advanced DI Patterns

```php
<?php

use yii\di\Container;

class ServiceFactory
{
    public function createPaymentProcessor(string $provider): PaymentProcessorInterface
    {
        $container = new Container();
        
        // ✅ Dynamic service resolution with proper typing
        switch ($provider) {
            case 'stripe':
                return $container->get(StripeProcessor::class); // StripeProcessor
            case 'paypal':
                return $container->get(PayPalProcessor::class); // PayPalProcessor
            default:
                throw new \InvalidArgumentException("Unknown provider: $provider");
        }
    }
    
    public function configureServices(): void
    {
        $container = new Container();
        
        // ✅ Runtime service configuration
        $container->set(
            EmailServiceInterface::class, function() {
                if (getenv('APP_ENV') === 'test') {
                    return new MockEmailService();
                }
                return new SmtpEmailService();
            }
        );
        
        // ✅ PHPStan understands the interface type
        $emailService = $container->get(EmailServiceInterface::class);
        $emailService->send('test@example.com', 'Subject', 'Body');
    }
}
```

## Behavior Examples

### Property and Method Access through Behaviors

```php
<?php

// Configuration in config/phpstan.php
return [
    'behaviors' => [
        \app\models\User::class => [
            \yii\behaviors\TimestampBehavior::class,
            \yii\behaviors\BlameableBehavior::class,
            \app\behaviors\SoftDeleteBehavior::class,
        ],
        \app\models\Post::class => [
            \yii\behaviors\SluggableBehavior::class,
            \app\behaviors\SeoOptimizedBehavior::class,
        ],
    ],
];

// Usage with behavior properties and methods
class UserService
{
    public function createUser(array $userData): User
    {
        $user = new User();
        $user->setAttributes($userData);
        
        // ✅ PHPStan knows about behavior properties
        // TimestampBehavior adds these automatically
        // $user->created_at and $user->updated_at are properly typed
        
        if ($user->save()) {
            // ✅ PHPStan knows about behavior methods
            // SoftDeleteBehavior adds these methods
            $user->restore(); // Method from SoftDeleteBehavior
            
            return $user;
        }
        
        throw new \RuntimeException('Failed to create user');
    }
    
    public function softDeleteUser(int $userId): bool
    {
        $user = User::findOne($userId);
        
        if ($user === null) {
            return false;
        }
        
        // ✅ PHPStan knows about behavior methods
        return $user->softDelete(); // Method from SoftDeleteBehavior
    }
    
    public function getDeletedUsers(): array
    {
        // ✅ PHPStan knows about behavior scopes
        return User::find()->deleted()->all(); // Scope from SoftDeleteBehavior
    }
}

class PostService
{
    public function createPost(array $postData): Post
    {
        $post = new Post();
        $post->setAttributes($postData);
        
        // ✅ PHPStan knows about SluggableBehavior properties
        // The slug property is automatically generated
        
        if ($post->save()) {
            // ✅ PHPStan knows about SeoOptimizedBehavior methods
            $post->generateMetaDescription(); // Method from SeoOptimizedBehavior
            $post->optimizeForSeo();          // Method from SeoOptimizedBehavior
            
            return $post;
        }
        
        throw new \RuntimeException('Failed to create post');
    }
    
    public function updateSeoData(Post $post): void
    {
        // ✅ PHPStan knows about behavior properties
        $post->meta_title = $post->generateSeoTitle();       // Method from behavior
        $post->meta_description = $post->generateMetaDesc(); // Method from behavior
        $post->save();
    }
}
```

## Header Collection Examples

### Dynamic Method Types

```php
<?php

use yii\web\HeaderCollection;

class ApiController extends \yii\web\Controller
{
    public function actionHeaders(): array
    {
        $headers = $this->response->headers; // HeaderCollection
        
        // ✅ PHPStan knows get() return types based on third parameter
        
        // Returns string (default behavior)
        $contentType = $headers->get('Content-Type'); // string
        
        // Returns string (explicit true for first match)
        $acceptLanguage = $headers->get('Accept-Language', null, true); // string
        
        // Returns array<int, string> (explicit false for all matches)
        $acceptEncodings = $headers->get('Accept-Encoding', null, false); // array<int, string>
        
        // Dynamic behavior - returns string|array<int, string>
        $firstOnly = $_GET['first_only'] ?? true;
        $cacheControl = $headers->get('Cache-Control', null, $firstOnly); // string|array<int, string>
        
        return [
            'content_type' => $contentType,
            'accept_language' => $acceptLanguage,
            'accept_encodings' => $acceptEncodings,
            'cache_control' => $cacheControl,
        ];
    }
    
    public function actionProcessHeaders(): void
    {
        $headers = $this->request->headers;
        
        // ✅ Proper type inference for different scenarios
        $authHeader = $headers->get('Authorization'); // string
        
        if ($authHeader !== null) {
            $this->processAuth($authHeader); // string parameter
        }
        
        // ✅ Array result handling
        $acceptHeaders = $headers->get('Accept', null, false); // array<int, string>
        
        foreach ($acceptHeaders as $accept) {
            // ✅ $accept is properly typed as string
            $this->processAcceptType($accept);
        }
    }
}
```

This comprehensive examples guide shows how the `Yii2` `PHPStan` extension provides precise type inference across all 
major `Yii2` patterns and use cases, making your code more maintainable and reducing runtime errors through static 
analysis.
