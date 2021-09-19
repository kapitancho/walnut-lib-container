# PSR-11 compatible container interface
A very easy to use DI-container implementation.

## Example:
```php
final class DbUserRepository implements UserRepository {
    public function __construct(
        private /*readonly*/ QueryExecutor $queryExecutor
    ) {}
    //...
}

$mapping = [
    UserRepository::class => DbUserRepository::class,
    QueryExecutor::class => PdoQueryExecutor::class,
    PdoQueryExecutor::class => [
        'dsn' => 'mysql:host=localhost;dbname=demo',
        'user' => 'root',
        'pwd' => 'root'
    ],
    UserService::class => fn(UserRepository $userRepository)
        => new UserService($userRepository, [
            'tokenValidityInMinutes' => 180
        ]
    ),
    LoggerInterface::class => FileLogger::class,
    FileLogger::class => LocalFileLogger::class
];
$container = new ContainerAdapter(new Container($mapping));
$container->get(UserService::class); //returns UserService with injected DbUserRepository
$container->get(LoggerInterface::class); //returns LocalFileLogger 
```