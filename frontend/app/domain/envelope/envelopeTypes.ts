export interface Envelope {
  uuid: string
  updatedAt: string
  currentBudget: string
  targetBudget: string
  name: string
  userUuid: string
  createdAt: string
  deleted: boolean
}

export interface EnvelopeState {
  envelopesData: {
    envelopes: Envelope[]
    totalItems: number
  } | null
  loading: boolean
  errorEnvelope: string | null
}
