# MyBG Help Center

Модуль довідкової системи для «Мій Богуслав — соціальна матриця міста».

## Можливості

- **Центр допомоги** — `/dopomoga`
- **Енциклопедія** — `/encyklopediya` (CT `documentation`, ієрархія через `field_doc_parent`)
- **Контекстна довідка** — плаваюча кнопка «?» на сторінках модулів
- **Порада дня** — блок на головній та в профілі
- **Onboarding-тур** — 5–7 кроків після першої реєстрації
- **Підказки полів** — ⓘ біля полів форм оголошень, роботи, бізнесу
- **Пошук по довідці** — `/dopomoga/poshuk` + індекс `help_docs_index`
- **Roadmap** — `/roadmap`
- **Місія** — `/misiya`

## Встановлення

```bash
drush en mybg_help -y
drush php:script scripts/setup_help_center.php
drush cr
drush cex -y
```

## Content type `documentation`

| Поле | Призначення |
|------|-------------|
| `field_doc_module` | Повʼязаний модуль (news, business, map…) |
| `field_doc_category` | Розділ енциклопедії (taxonomy `help_category`) |
| `field_doc_parent` | Батьківська стаття |
| `field_doc_weight` | Сортування |
| `field_doc_faq` | Показувати в FAQ |
| `field_doc_is_tip` | Порада дня |
| `field_doc_video` | Посилання на відео |

## Майбутнє (за специфікацією)

- AI-помічник (чат з базою знань)
- Інтерактивні тури по модулях
- Голосування на roadmap
- Мультимовність
