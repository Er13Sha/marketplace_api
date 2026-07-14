# marketplace_api

## Demo catalog seed

Symfony equivalent of Laravel seeders/factories is usually a console command or
Doctrine fixtures. This project uses a production-safe console command because
the Docker entrypoint can run it after migrations on the first application
startup.

```bash
php bin/console app:seed-demo-products --count=20000
```

The command always appends `--count` new products. For example, if the catalog
already has 13 products, `--count=20000` adds 20,000 more products.

In Docker Compose the backend runs it after migrations through:

```yaml
SEED_FAKE_PRODUCTS: "20000"
```

Set `SEED_FAKE_PRODUCTS: "0"` to disable automatic demo product creation.
The Docker entrypoint passes `--if-empty`, so automatic seeding only runs for an
empty catalog and does not add another batch on every container restart.
