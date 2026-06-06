# Site contacts (Config Pages content export)

Значення полів «Контакти сайту» зберігаються як **контент** (не в `config/sync`).
Для переносу між середовищами використовується **Single Content Sync**.

## Редагування

Адмінка: **Configuration → MyBG → Site contacts**  
(`/admin/config/mybg/site-contacts`)

Ролі з правом редагування: Editor, Moderator.

## Деплой (prod/staging)

```bash
drush config:import -y
drush content:import web/content/site_contacts/config_pages-site_contacts-*.yml -y
drush cr
```

## Після зміни контактів на dev — оновити файл у git

```bash
drush content:export config_pages web/content/site_contacts \
  --entities=d4990401-6a86-4451-aa3c-8e390b2b6cbf -y
git add web/content/site_contacts/
git commit -m "Update site contacts"
```

## Що в config/sync (структура)

- `config_pages.type.site_contacts` — тип сторінки налаштувань
- `field.*.config_pages.site_contacts.*` — поля
- `block.block.mybg_radix_site_contacts` — розміщення блоку у футері
