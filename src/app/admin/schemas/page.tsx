import { redirect } from "next/navigation";

// Schema's staan nu per type in een eigen onderdeel.
export default function Page() {
  redirect("/admin/trainingsschemas");
}
