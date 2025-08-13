# Custom Rental Car Manager for Costabilerent

**Version:** 1.0.0 (August 2025)  
**Author:** Totaliweb  
**Website:** [https://www.totaliweb.com](https://www.totaliweb.com)  
**Plugin URI:** https://www.totaliweb.com/plugins/custom-rental-car-manager

---

## Descrizione

Il plugin **Custom Rental Car Manager** è un sistema completo e professionale per la gestione autonoleggio di auto e scooter, ideato per l'agenzia Costabilerent a Ischia. Include gestione veicoli, tariffe (base e personalizzate), disponibilità, servizi extra, assicurazioni, automazioni email, calendario dinamico e REST API. Le funzionalità front-end sono demandate al tema dedicato.

## Caratteristiche principali

- Gestione veicoli: modelli, stock, tariffe, immagini, servizi
- Tariffe personalizzate per date/ scenari
- Disponibilità individuale, ricorrente e settimanale
- Integrazione pagamenti Stripe via API
- REST API per integrazione front-end su tema dedicato
- Calendario amministrativo in tempo reale
- Automazioni email dinamiche
- Compatibilità con PHP 8.0+, WP ultime versioni
- Internazionalizzazione facile (lingua principale inglese)
- Standard di sicurezza, performance e scalabilità

## Installazione

1. Carica la directory `custom-rental-car-manager/` nella cartella `/wp-content/plugins/`
2. Attiva il plugin via Plugins nel backend WordPress
3. Configura le impostazioni dal menu “Costabilerent” → **Impostazioni**
4. Crea veicoli, tariffe, sedi, servizi, assicurazioni tramite admin
5. Copia la directory `costabilerent-theme/` nella cartella `/wp-content/themes/` e attiva il tema da **Aspetto** → **Temi**
6. Per maggiori dettagli consulta la [documentazione aggiornata](https://www.totaliweb.com/docs/custom-rental-car-manager)

## Uso

- Utilizza gli shortcode del tema `[crcm_search_form]`, `[crcm_vehicle_list]`, `[crcm_booking_form]`, `[crcm_customer_dashboard]`
- Personalizza layout e stile in front-end tramite template override
- Gestisci prenotazioni, veicoli, calendario in backend

## Endpoint & Hook disponibili

Il plugin espone endpoint REST che forniscono i dati degli shortcode e relativi hook per ulteriori personalizzazioni:

| Endpoint | Descrizione | Hook dati |
| --- | --- | --- |
| `/wp-json/crcm/v1/search-form` | Dati per il form di ricerca (sedi) | `crcm_search_form_data` |
| `/wp-json/crcm/v1/vehicles` | Elenco veicoli disponibili | `crcm_vehicle_list_data` |
| `/wp-json/crcm/v1/booking-form` | Dati per il form di prenotazione | `crcm_booking_form_data` |
| `/wp-json/crcm/v1/customer-dashboard` | Dati area cliente (richiede login) | `crcm_customer_dashboard_data` |

Gli hook permettono al tema o ad altre estensioni di modificare gli array restituiti prima dell'invio al client.

## Personalizzazione e sviluppo

- Modifica i file in `/inc/` per estensioni
- Usa `register_rest_route()` per API custom
- Traduci facilmente modificando i file `.po/.mo` in `/languages/`

## Autenticazione REST API

Le chiamate all'API REST (`/wp-json/crcm/v1/`) richiedono una forma di autenticazione e verificano sempre un **nonce** o i **ruoli** dell'utente:

- **Nonce WordPress** – per richieste provenienti dal frontend, invia l'header `X-WP-Nonce` generato da WordPress.
- **Ruoli WordPress** – per richieste server-to-server o backend, l'autorizzazione avviene tramite `current_user_can()`.
  - Gli endpoint gestionali (`/bookings` `GET`, `/calendar`) richiedono la capability `manage_options` o `crcm_manage_bookings`.
  - Gli endpoint pubblici (veicoli, disponibilità, creazione prenotazioni) accettano utenti con capability `read`.

Gli endpoint pubblici applicano un semplice rate limiting per indirizzo IP (max 100 richieste ogni 10 minuti).

## Supporto e aggiornamenti

Per supporto, suggerimenti o bug report, apri una issue su [GitHub Repository Link].

---

## Licenza
GPL v2 o superiore.  
Plugin open source, pienamente conforme con Christ WordPress.org standards.

---

## Ringraziamenti

Sviluppato da **Totaliweb** per Costabilerent, ispirato ai migliori standard del settore.

---

## Note

> *Questo plugin è in sviluppo attivo. Sempre aggiornato con le ultime best practices WP e PHP.*

---

## Contatti

- Website: [https://www.totaliweb.com](https://www.totaliweb.com)
- Email: info@totaliweb.com
- Supporto: https://github.com/antonio86itna/custom-rental-car-manager/issues
