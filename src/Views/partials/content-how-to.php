<?php

/**
 * How To — shared content partial.
 *
 * Included by both:
 *   - partials/modal-how-to.php  (inside <dialog>)
 *   - pages/how-to.php           (standalone page)
 *
 * Accepts optional $contentHeadingLevel (default 'h2').
 * Modal wrappers pass 'h3' so sections nest under the dialog's <h2> title.
 * Standalone pages use the default 'h2' to sit directly under the page <h1>.
 */
$contentHeadingLevel ??= 'h2';
?>
<section>
  <<?= $contentHeadingLevel ?>>Getting Started</<?= $contentHeadingLevel ?>>
  <p>Creating your account takes just a minute.</p>
  <ol>
    <li><strong>Sign up.</strong> Provide your first and last name, a username, email address, password, and ZIP code. Optionally add a street address to be assigned to your nearest neighborhood automatically.</li>
    <li><strong>Accept the Terms of Service.</strong> Review and accept the current terms before borrowing or lending. When terms are updated, you&rsquo;ll be prompted to accept the new version.</li>
    <li><strong>Set up your profile.</strong> Pick an avatar from the platform&rsquo;s illustration library or upload your own photo, write a short bio, and choose a contact preference so neighbors know a bit about you.</li>
    <li><strong>Explore.</strong> Browse available tools by category or location, bookmark anything you&rsquo;re interested in, and list your own tools to share with the community.</li>
  </ol>
</section>

<section>
  <<?= $contentHeadingLevel ?>>For Borrowers</<?= $contentHeadingLevel ?>>
  <p>Finding and borrowing a tool from your neighbors is straightforward.</p>
  <ol>
    <li><strong>Search for a tool.</strong> Browse the catalog or use keyword search with autocomplete suggestions, category filters, ZIP code, distance radius, and maximum fee to find what you need nearby. Bookmark tools you&rsquo;re interested in to save them for later.</li>
    <li><strong>Submit a borrow request.</strong> Choose your loan duration and send a request to the tool&rsquo;s owner. The tool is temporarily held while the lender reviews your request.</li>
    <li><strong>Wait for approval.</strong> The lender reviews your request and reputation, then approves or denies it. You&rsquo;ll receive a notification either way.</li>
    <li><strong>Sign the waiver.</strong> Once approved, acknowledge the tool&rsquo;s current condition and accept a liability waiver before pickup. All three acknowledgments&mdash;borrow terms, condition, and liability&mdash;must be completed.</li>
    <li><strong>Pay the deposit.</strong> If the lender requires a refundable deposit, complete payment before picking up the tool. Your deposit is held securely and returned once the tool comes back in good condition.</li>
    <li><strong>Pick up the tool.</strong> Coordinate a pickup with the lender. You&rsquo;ll each receive a unique six-character handover code&mdash;exchange codes at the handoff to officially start the loan period.</li>
    <li><strong>Return when done.</strong> Bring the tool back in the same condition. Exchange handover codes again to confirm the return. Any deposit held is released once the lender confirms the tool&rsquo;s condition.</li>
    <li><strong>Rate the lender.</strong> After the return is confirmed, leave a rating to help build trust across the community.</li>
  </ol>
</section>

<section>
  <<?= $contentHeadingLevel ?>>For Lenders</<?= $contentHeadingLevel ?>>
  <p>Share the tools you aren&rsquo;t using and help your community.</p>
  <ol>
    <li><strong>List your tool.</strong> Add up to six photos&mdash;with drag-and-drop upload and focal-point repositioning&mdash;a description, the tool&rsquo;s current condition, and a category so borrowers can find it easily. If the tool is fuel-powered, specify the fuel type.</li>
    <li><strong>Set your terms.</strong> Set a daily rental fee and a suggested loan duration. These details are displayed on the tool&rsquo;s listing page so borrowers know what to expect.</li>
    <li><strong>Manage availability.</strong> Block out dates when a tool isn&rsquo;t available, or toggle a listing on and off as needed from your dashboard.</li>
    <li><strong>Review requests.</strong> When someone requests your tool, you&rsquo;ll be notified and can see their profile and reputation on your lender dashboard. Approve or deny each request at your discretion.</li>
    <li><strong>Confirm pickup.</strong> Exchange six-character handover codes with the borrower at pickup to officially start the loan period. Codes expire after 24 hours if unused.</li>
    <li><strong>Manage the loan.</strong> Track active loans from your dashboard. If the borrower needs more time, you can extend the loan duration.</li>
    <li><strong>Verify the return.</strong> When the tool comes back, inspect its condition and exchange return codes to confirm. If everything checks out, any deposit is released automatically.</li>
    <li><strong>Rate the borrower.</strong> After the return is confirmed, leave a rating to help the community identify reliable neighbors.</li>
  </ol>
</section>

<section>
  <<?= $contentHeadingLevel ?>>Safety &amp; Trust</<?= $contentHeadingLevel ?>>
  <p>NeighborhoodTools is built to keep every transaction fair and transparent.</p>
  <ol>
    <li><strong>Deposit protection.</strong> Lenders can require a refundable deposit that&rsquo;s held while the tool is on loan. Deposits are released on return, or partially/fully retained if the tool is damaged, lost, or unreturned.</li>
    <li><strong>Condition documentation.</strong> Each tool&rsquo;s condition&mdash;new, good, fair, or poor&mdash;is recorded when listed and formally acknowledged by the borrower through a waiver before every loan. This creates a clear baseline if questions arise later.</li>
    <li><strong>Handover codes.</strong> Auto-generated six-character codes confirm both pickup and return, so there&rsquo;s never ambiguity about who has what or when transfers happened. Each code expires after 24 hours.</li>
    <li><strong>Ratings &amp; reputation.</strong> After each completed borrow, both lender and borrower can rate each other on a five-star scale. Ratings are averaged and displayed on profiles, building trust across the community.</li>
    <li><strong>Incident reporting.</strong> If something goes wrong&mdash;damage, theft, loss, injury, a late return, or a condition disagreement&mdash;either party can file an incident report within 48 hours of the return.</li>
    <li><strong>Dispute resolution.</strong> When an incident can&rsquo;t be resolved between the parties, a formal dispute can be opened. Both sides and administrators can exchange messages until the issue is resolved or dismissed.</li>
  </ol>
</section>

<section>
  <<?= $contentHeadingLevel ?>>Community</<?= $contentHeadingLevel ?>>
  <p>NeighborhoodTools is more than borrowing and lending&mdash;it&rsquo;s about building connections with your neighbors.</p>
  <ol>
    <li><strong>Neighborhood events.</strong> Members can create and browse local events&mdash;tool workshops, repair meetups, community clean-ups, and more. RSVP to join and meet people nearby.</li>
    <li><strong>Notifications.</strong> Stay up to date with notifications for borrow requests, approvals, due dates, returns, ratings, and more. Customize which optional notifications you receive from your notification preferences page.</li>
    <li><strong>Bookmarks.</strong> Save tools you&rsquo;re interested in to your bookmarks list for quick access later. Bookmark any listing from its detail page or directly from search results.</li>
    <li><strong>Your dashboard.</strong> Track everything in one place&mdash;active borrows, pending requests, listed tools, loan history, and your reputation score. Separate lender and borrower views keep things organized.</li>
    <li><strong>Password recovery.</strong> If you forget your password, use the forgot-password link on the login page. You&rsquo;ll receive a reset link by email to create a new one.</li>
  </ol>
</section>
