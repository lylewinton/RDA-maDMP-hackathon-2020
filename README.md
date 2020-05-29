# RDA-maDMP-hackathon-2020
This project holds part of the results of the RDA DMP Common Standards working group Hackathon on maDMPs,
specifically part of the code developed for "Team issue-17-19".
https://gist.github.com/peterneish/92b59a90f5e05096c3709669f459c749

Details on the hackathon can be found here
https://github.com/RDA-DMP-Common/hackathon-2020

The common standard can be found here
https://github.com/RDA-DMP-Common/RDA-DMP-Common-Standard

## Function
The uploaddmp.php script is the main code. The code is functional but not complete.
A config.php file is needed (see the template) to hold authentication settings.
Essentially the code does the following:
* find a figshare user account id given an email address.
* (rest of actions are password challenge protected)
* parse a given maDMP JSON from a URL (just plain HTTP at this point) [TODO: dmptool integration]
* create a figshare Project, as a placeholder for all datasets to be uploaded/published
* create a DMP item within the project with the referenced DMP ID, title, description
* reserve a DOI for the DMP item, for future publishing (or possible update back in the DMP system)
* uploading the maDMP JSON and PDF as files as part of the item. [TODO]

This script is eventually intended for direct call with URL parameters (email etc.),
to be used as an automated call from a other systems, perhaps your DMP system.
The HTML form is included only for testing the call parameters.
In future the response might be JSON.

## General concept
From an assumed DMP management system, a mechanism is needed to uploading and maintaining DMP's into figshare.
A DMP would map to a single "item" in figshare (referred to as article in the API).
This item could containing a PDF and the maDMP JSON file, for long-term retention, for interoperability
with other systems, or for transfer to other institutions.

    DMP -- 1..1 --> figshare.article
      title => article.title
      description => article.description
      contributor => article.authors
      dmp_id => article.references

Now for datasets within maDMPs, some of these might map to published datasets within figshare.
To identify which datasets are associated to a DMP, you could pre-populate a structure in figshare
where each DMP is represented by a figshare "project" that contains the DMP "item".
This approach encourages researchers to start publishing datasets in figshare within their DMP,
and essentially next to their uploaded DMP item.
As an additional benefit, the project or the DMP can be published.

    DMP -- 1..1 --> figshare.article  (as above, but created within the project)
    DMP -- 1..1 --> figshare.project
      title => project.title
      description => project.description
      contributor => project.collaborators
      dmp_id => ? (unsure where to put this)
    DMP.dataset <-- 1..1 -- figshare.project.article

Any articles (which can represent datasets of files) that are found within the figshare project
could be imported back into your maDMP system as the user creates them, as additional DMP datasets.
If the DMP in updated within the DMP system (including if datasets are imported) this might
then trigger to push a new version of the DMP back to the figshare.article .

Note: The DMP->projects concept within the common standard information model is not being represented here, only DMP's and datasets.
