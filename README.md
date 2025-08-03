# Custom Rental Car Manager for Costabilerent

**Version:** 1.0.0 (August 2025)  
**Author:** Totaliweb  
**Website:** [https://www.totaliweb.com](https://www.totaliweb.com)  
**Plugin URI:** https://www.totaliweb.com/plugins/custom-rental-car-manager

---

## Descrizione

Il plugin **Custom Rental Car Manager** è un sistema completo e professionale per la gestione autonoleggio di auto e scooter, ideato per l'agenzia Costabilerent a Ischia. Include gestione veicoli, tariffe (base e personalizzate), disponibilità, servizi extra, assicurazioni, prenotazioni online con checklist passo-passo, pagamenti Stripe, area clienti, notifiche email, calendario dinamico e REST API full.

## Caratteristiche principali

- Gestione veicoli: modelli, stock, tariffe, immagini, servizi
- Tariffe personalizzate per date/ scenari
- Disponibilità individuale, ricorrente e settimanale
- Prenotazioni con wizard, checkout Stripe e numero prenotazione unico
- Area riservata clienti e gestione prenotazioni
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

## Uso

- Inserisci shortcode `[crcm_search]`, `[crcm_list]`, `[crcm_booking]`, `[crcm_area]` nelle pagine desiderate
- Personalizza layout e stile in front-end tramite template override
- Gestisci prenotazioni, veicoli, calendario in backend

## Personalizzazione e sviluppo

- Modifica i file in `/inc/` per estensioni
- Usa `register_rest_route()` per API custom
- Traduci facilmente modificando i file `.po/.mo` in `/languages/`

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
