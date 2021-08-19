# Welcome to OpenY Search smoke tests documentation

In order for OpenY Search being tested in a short timeframe, please follow steps below

# Component: Open Y Search								

## Enable/Hide Search form

### User

Administrator

### Steps

0. Verify you see a search icon in the header and it opens search form. 
1. Log in as Admin 
2. Go to Appearance -> Installed themes 
3. Find ""Open Y Carnation"" and open settings 
4. Verify there is a collapsible search fieldset and ""Display the search form"" checkbox
5. Verify after enabling/disabling checkbox search icon and form appears/disappears from the header

### Expected Results

1. There are settings in the Carnation theme to show/hide search form 
2. Settings work

## Configure search using Apache Solr/Open Y search (Solr back-end)

### User

Administrator

### Steps

1. Login as Admin
2. Go to Extend
3. Install module ""Open Y Search API"" and all required modules
4. Uninstall ""Open Y Google Search"" if it is installed.
5. Make sure modules Search API, Search API Solr are installed
6. Go to Configuration -> Search and metadata -> Search API (/admin/config/search/search-api)
7. Verify you can see a server called ""Solr search"" disabled by default
8. Enable this server using Operations
9. Edit this server 
10. Modify Solr host field and enter there IP address (for builds and local env 127.0.0.1)
11. Modify Solr core field and enter there the name that identifies the Solr core on the server (for example ""d9_sandbox_carnation_custom"")
12. Save
13. Verify solr server is working and there are no error messages
14. On the Configuration -> Search and metadata -> Search API (/admin/config/search/search-api) page find index called ""Search Content""
15. Open for editing ""Search Content"" index 
16. Chose Server ""Solr search""
17. Click on the Search Content link to open View page
18. On the View page in the fieldsel called ""Start indexing now"" click on the button ""Index now""
19. Verify indexing completed successfully 

### Expected Results

1. There is a module ""Open Y Search API"" and Search API Solr
2. There is a server called ""Solr Search"" disabled by default 
3. Server Configuration form is wotking without any issues 
4. Search content idex present 
5. Indexing content works without any issues

## Verify search is working/Open Y search (Solr back-end)

### User

Administrator / Anonymous

### Steps

1. Go to the homepage 
2. Verify there is a search icon that opens search form when you click on it 
3. Enter some keywords,  for example: ymca; Swim Lessons, etc
4. Verify you see results on the page 
5. Find any landing page with some text on it and try to search by keywords from that landing page 
6. Verify search is working and show correct results

### Expected Results

1. Search form is working 
2. Search results are correct
