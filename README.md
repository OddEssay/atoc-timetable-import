atoc-timetable-import
=====================

Import the ATOC Timetable Feed into MongoDB using PHP.

This is a really simple script that sacrifices optimization for clarity of understanding.

Currently the source is provided for anyone interested in how a rail data import and API might fit together, but at best should be considered "Alpha" release. That said, I welcome feedback on the project even as it stands.


Data Sources
============
Date is provided at http://data.atoc.org/data-download - You'll need to sign up to get access.

Data Format Documentation
=========================
The data format documentation is lifted directly from the ATOC CIF documentation.


Casting of Data
===============
Because we plan to put the data into MongoDB where strings and numbers query differently, we cast strings as such when we need to make sure PHP doesn't miss identify a value as a number.

Licence
=======
This code is provided as is, without warranty.

FAQ
===
How Long Does The Import Take?
------------------------------
On a virtual machine running on Windows 8, with SSD the import takes around 10 mins. On a Bytemark BigV VM, using SATA disks it takes around 20 mins.
