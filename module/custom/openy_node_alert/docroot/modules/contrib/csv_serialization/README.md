Notes about the CSV encoder
--------------------------------------------------------------------------------

The CSV format has a number of inherent limitations not present in other formats
(e.g., JSON or XML). Namely, they are:
* A CSV cannot support an array with a depth greater than three
* Each row in a CSV must share a common set of headers with all other rows

For these reasons, the CSV format is not well-suited for encoding all data 
structures--only data with a specific structure. The provided CSV encoder
 does not support data structures that do not meet these limitations.
