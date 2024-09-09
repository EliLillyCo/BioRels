#!/bin/bash

#NOTE: SGE recognizes lines that start with #$ even though bash sees the # as a comment.
#      -N tells SGE the name of the job.
#      -j y tells SGE to merge STDERR with STDOUT.
#      -cwd tells SGE to execute in the current working directory (cwd).

#$ -S /bin/bash
#$ -j y
#$ -cwd

sleep 6
CMDFILE=$1
CHUNK=`expr $SGE_TASK_ID + $SGE_TASK_STEPSIZE - 1`
CMD=$(sed -n "$SGE_TASK_ID,$CHUNK"p $CMDFILE)
eval "$CMD"
