"use client";

import { CircleAlert, CircleCheck, LoaderCircle } from "lucide-react";
import type { ComponentProps, ReactNode } from "react";
import { useFormStatus } from "react-dom";

export function SubmitButton({
  children,
  pendingText = "Bezig…",
  className = "btn btn-ink w-full",
  ...props
}: ComponentProps<"button"> & { pendingText?: string }) {
  const { pending } = useFormStatus();
  return (
    <button type="submit" disabled={pending} className={className} {...props}>
      {pending ? (
        <>
          <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> {pendingText}
        </>
      ) : (
        children
      )}
    </button>
  );
}

export function FormAlert({ error, success }: { error?: string; success?: string }) {
  if (!error && !success) return null;
  return (
    <div
      role={error ? "alert" : "status"}
      className={`flex gap-3 rounded-xl border p-4 text-sm ${
        error ? "border-danger/30 bg-danger/5 text-danger" : "border-success/30 bg-success/5 text-success"
      }`}
    >
      {error ? <CircleAlert className="size-5 shrink-0" aria-hidden="true" /> : <CircleCheck className="size-5 shrink-0" aria-hidden="true" />}
      <p>{error ?? success}</p>
    </div>
  );
}

type FieldProps = ComponentProps<"input"> & {
  label: string;
  name: string;
  error?: string;
  hint?: ReactNode;
};

export function Field({ label, name, error, hint, id, ...props }: FieldProps) {
  const fieldId = id ?? `f-${name}`;
  return (
    <div>
      <label htmlFor={fieldId} className="label">
        {label}
      </label>
      <input
        id={fieldId}
        name={name}
        className="input"
        aria-invalid={error ? true : undefined}
        aria-describedby={error ? `${fieldId}-error` : hint ? `${fieldId}-hint` : undefined}
        {...props}
      />
      {hint && !error && (
        <p id={`${fieldId}-hint`} className="mt-1.5 text-xs text-muted">
          {hint}
        </p>
      )}
      {error && (
        <p id={`${fieldId}-error`} className="field-error">
          {error}
        </p>
      )}
    </div>
  );
}

export function SelectField({
  label,
  name,
  error,
  options,
  placeholder,
  id,
  ...props
}: ComponentProps<"select"> & {
  label: string;
  name: string;
  error?: string;
  placeholder?: string;
  options: { id: string; label: string }[];
}) {
  const fieldId = id ?? `f-${name}`;
  return (
    <div>
      <label htmlFor={fieldId} className="label">
        {label}
      </label>
      <select
        id={fieldId}
        name={name}
        className="input"
        aria-invalid={error ? true : undefined}
        aria-describedby={error ? `${fieldId}-error` : undefined}
        {...props}
      >
        {placeholder && <option value="">{placeholder}</option>}
        {options.map((o) => (
          <option key={o.id} value={o.id}>
            {o.label}
          </option>
        ))}
      </select>
      {error && (
        <p id={`${fieldId}-error`} className="field-error">
          {error}
        </p>
      )}
    </div>
  );
}
