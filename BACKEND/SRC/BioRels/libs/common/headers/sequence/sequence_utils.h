#ifndef SEQUENCE_UTILS_H
#define SEQUENCE_UTILS_H

#include "headers/statics/grouplist.h"
#include "headers/sequence/seqchain.h"

namespace protspace
{
void loadSequenceAlignment(const GroupList<SequenceChain> &mListSequences,
                           const std::string& pFile,
                          std::vector<std::string> & pListSeqs,
                          std::vector<short>& pListchains);
void loadSequenceAlignment(const std::string& pFile,
                          std::vector<std::string> & pListSeqs,
                           std::vector<std::string>& listNames);
}
#endif // SEQUENCE_UTILS_H

