atoc-timetable-import
=====================

Import the ATOC Timetable Feed into MongoDB using PHP.

This is a really simple script that sacrifices optimization for clarity of understanding.

Data Sources
============

Date is provided at http://data.atoc.org/data-download - You'll need to sign up to get access.

Casting of Data
===============
Because we plan to put the data into MongoDB where strings and numbers query differently, we cast strings as such when we need to make sure PHP doesn't miss identify a value as a number.

Licence
=======

This code is provided as is, without warranty.

