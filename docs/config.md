# Bundle Configuration

Created via `./bin/console config:dump-reference DbpRelaySublibraryBundle | sed '/^$/d'`

```yaml
# Default configuration for "DbpRelaySublibraryBundle"
dbp_relay_sublibrary:
  # The REST API endpoint to use
  api_url:              ~ # Required, Example: 'https://api-eu.hosted.exlibrisgroup.com'
  # The API key for the REST API
  api_key:              ~ # Required, Example: your_key
  # The API key for the analytics API (defaults to api_key)
  analytics_api_key:    ~ # Example: your_key
  # Makes all write operations error out, even if the API key would allow them.
  readonly:             false
  person_local_data_attributes:
    # The person local data attribute to request for the person email address
    email:                email
    # The person local data attribute to request for the person ALMA ID
    alma_id:              almaId
  organization_local_data_attributes:
    # The organization local data attribute that contains the library code
    code:                 code
  # The full paths to the used analytics reports
  analytics_reports:
    # Full path to the report containing information about all book offers
    book_offer:           '/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/Bestand-Institute-pbeke'
    # Full path to the report containing information about all book orders
    book_order:           '/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/PO-List-pbeke_bearb_SF_6c'
    # Full path to the report containing information about all book loans
    book_loan:            '/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/Ausleihen-Institute-pbeke'
    # Full path to the report containing information about the budget of the libraries
    budget:               '/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/Funds-List-SF_2'
    # Full path to the report containing information about when the analytics were last updated
    update_check:         '/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/Analytics-Updates'
  authorization:
    policies:             []
    roles:
      # Returns true if the user is allowed to use the dispatch API.
      ROLE_LIBRARY_MANAGER: 'false'
    resource_permissions: []
    attributes:
      # Returns the list of sublibrary IDs the user has manager rights in
      SUBLIBRARY_IDS:       '[]'
      # Returns the list of Alma library IDs the user has manager rights in
      ALMA_LIBRARY_IDS:     '[]'
```

Example configuration:

```yaml
dbp_relay_sublibrary:
  api_url: 'https://api-eu.hosted.exlibrisgroup.com'
  api_key: '%env(SUBLIBRARY_ALMA_API_KEY)%'
  authorization:
    roles:
      ROLE_LIBRARY_MANAGER: 'user.get("LIBRARY_MANAGER_AT", []) !== []'
    attributes:
      SUBLIBRARY_IDS: 'user.get("LIBRARY_MANAGER_AT", [])'
      ALMA_LIBRARY_IDS: 'user.get("ALMA_LIBRARY_MANAGER_AT", [])'
```

## Prerequisites

* An API key for the [Ex Libris ALMA API](https://developers.exlibrisgroup.com/alma/apis/)
* An API key for the [Ex Libris ALMA Analytics API](https://developers.exlibrisgroup.com/alma/apis/analytics/)
* Various pre-configured analytics reports in your ALMA Analytics account (TODO)