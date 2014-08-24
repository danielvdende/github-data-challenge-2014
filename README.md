GitHub User-Languages
==========================
##Steps to run the analysis
First run the BigQuery query:

```SELECT repository_owner, repository_language
FROM [githubarchive:github.timeline]
WHERE repository_language!='null'
GROUP BY repository_owner, repository_language```

Now perform the following steps:

1.  Download the result of the BigQuery query
2.  Set up your config.php. It should contain the following variables:
    * ```$dbuser```: the username for your local db installation
    * ```$dbpass```: the password for the user of your local db installation.
    * ```$languages```: list of language strings that are to be used.
3.  Run bigquery_handler.php to process the data file (make sure you set up your database correctly!)
3.  Run cross-compute.php to get the inter-language statistics (i.e. how many people speak A and B)
4.  Enjoy the visualization by firing up ```index.html``` :)

## License & credits
This entry was built by [Daniel van der Ende](https://danielvdende.com). It is licensed under the [MIT License](https://github.com/danielvdende/github-data-challenge-2014/blob/master/LICENSE)
