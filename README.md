# Eggheads mocks utils

**Запустить контейнер в фоновом режиме:**
```shell
docker-compose up -d
```

**Перейти в контейнер:**
```shell
docker-compose exec -u www-data php7 bash
```

**Запуск тестов внутри контейнера:**
```shell
composer test
```