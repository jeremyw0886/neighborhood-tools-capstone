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
    <li><strong>Sign up.</strong> Provide your name, a username, email address, password, and your ZIP code. If you include a street address, the platform automatically assigns you to your nearest neighborhood.</li>
    <li><strong>Accept the Terms of Service.</strong> Review and accept the current terms before borrowing or lending. When terms are updated, you&rsquo;ll be prompted to accept the new version.</li>
    <li><strong>Explore.</strong> Browse available tools in your area, bookmark anything you&rsquo;re interested in, and list your own tools to share with the community.</li>
  </ol>
</section>

<section>
  <<?= $contentHeadingLevel ?>>For Borrowers</<?= $contentHeadingLevel ?>>
  <p>Finding and borrowing a tool from your neighbors is straightforward.</p>
  <ol>
    <li><strong>Search for a tool.</strong> Browse the catalog or use keyword search, category filters, ZIP code, and maximum fee to find what you need nearby.</li>
    <li><strong>Submit a borrow request.</strong> Choose your loan duration and send a request to the tool&rsquo;s owner. The tool is temporarily held while the lender reviews your request.</li>
    <li><strong>Wait for approval.</strong> The lender reviews your request and reputation, then approves or denies it. You&rsquo;ll receive a notification either way.</li>
    <li><strong>Sign the waiver.</strong> Once approved, acknowledge the tool&rsquo;s current condition and accept a liability waiver before pickup. All three acknowledgments&mdash;borrow terms, condition, and liability&mdash;must be completed.</li>
    <li><strong>Pick up the tool.</strong> Coordinate a pickup with the lender. You&rsquo;ll each receive a unique six-character handover code&mdash;exchange codes at the handoff to officially start the loan period.</li>
    <li><strong>Return when done.</strong> Bring the tool back in the same condition. Exchange handover codes again to confirm the return. Any deposit held is released once the lender confirms the tool&rsquo;s condition.</li>
  </ol>
</section>

<section>
  <<?= $contentHeadingLevel ?>>For Lenders</<?= $contentHeadingLevel ?>>
  <p>Share the tools you aren&rsquo;t using and help your community.</p>
  <ol>
    <li><strong>List your tool.</strong> Add a photo, description, and the tool&rsquo;s current condition. Assign a category so borrowers can find it easily.</li>
    <li><strong>Set your terms.</strong> Set a rental fee, a suggested loan duration, and optionally require a refundable deposit. These details are displayed on the tool&rsquo;s listing page so borrowers know what to expect.</li>
    <li><strong>Review requests.</strong> When someone requests your tool, you&rsquo;ll be notified and can see their profile and reputation on your lender dashboard. Approve or deny each request at your discretion.</li>
    <li><strong>Confirm pickup.</strong> Exchange six-character handover codes with the borrower at pickup to officially start the loan period. Codes expire after 24 hours if unused.</li>
    <li><strong>Verify the return.</strong> When the tool comes back, inspect its condition and exchange return codes to confirm. If everything checks out, any deposit is released automatically.</li>
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
