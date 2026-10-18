/* Storefront behaviour.
 *
 * One module per behaviour, imported once and loading everywhere: each module
 * finds what it works on and steps back when the page holds none of it, so a
 * page that has no cart costs nothing for the cart's script. A behaviour only
 * one page needs belongs to that page instead of here.
 */

import "./components/header";
import "./components/catalog";
import "./components/quantity-selector";
import "./components/auth-modal";
import "./components/form-submit";
import "./components/password-policy";
import "./components/account-form";
import "./components/checkable-fields";
import "./components/filter-dropdown";
import "./components/scroller-nav";
