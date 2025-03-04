# Authorization

The bundle currently only has one bundle role `ROLE_LIBRARY_MANAGER` which if
returning `true` allows the user to use the dispatch API.

To restrict the a user to a set of libraries the following attributes need to be defined:

* `SUBLIBRARY_IDS` - a list of sublibrary IDs the user has manager rights in.
  These correspond to the `identifier` field provided by the sublibrary provider.
* `ALMA_LIBRARY_IDS` - a list of Alma library IDs the user has manager rights in.
  These correspond to `code` field provided by the sublibrary provider.
