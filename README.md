# Tiszaszalka SE weboldal

Egyszeru PHP alapu weboldal falusi focicsapatnak.

## Oldalak

- `index.php` - publikus weboldal
- `admin.php` - egyszeru adatfelviteli felulet
- `data/site.json` - mentett hirek, meccsek, tabella, galeria es kapcsolat
- `assets/img/gallery/` - adminbol feltoltott kepek helye

## Futtatas

```bash
php -S localhost:8000
```

Publikus oldal: `http://localhost:8000`

Admin felulet: `http://localhost:8000/admin.php`

Megjegyzes: az admin felulet jelenleg nincs jelszoval vedve. Eles tarhelyen ezt kulon be kell allitani.
