'use client'

import { useUser } from '../domain/user/userHooks'
import EnvelopeManagement from '../components/EnvelopeManagement'
import {useEnvelopes} from "../domain/envelope/envelopeHooks";

export default function EnvelopesPage() {
  const { user, loading } = useUser()

  if (loading) {
    return <div className="flex justify-center items-center h-screen">
      <div className="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-primary"></div>
    </div>
  }

  if (!user) {
    return <div className="text-center mt-8">Please sign in to view your envelopes.</div>
  }

  return (
      <div>
        <EnvelopeManagement />
      </div>
  )
}
